<?php
/**
 * Handles the Stripe billing account page
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUM\Stripe\Invoice as StripeInvoice;
use WPUM\Stripe\Stripe;
use WPUserManager\Stripe\Controllers\Invoices;
use WPUserManager\Stripe\Controllers\Products;
use WPUserManager\Stripe\Models\User;

/**
 * Account
 */
class Account {

	/**
	 * @var string
	 */
	protected $public_key;

	/**
	 * @var string
	 */
	protected $secret_key;

	/**
	 * @var string
	 */
	protected $gateway_mode;

	/**
	 * @var Billing
	 */
	protected $billing;

	/**
	 * @var Products
	 */
	protected $products;

	/**
	 * Whether each user still owes a payment, by user ID, for this request.
	 *
	 * @var array
	 */
	protected static $owes_payment = array();

	/**
	 * Set while checking payment status, since the check itself tests capabilities.
	 *
	 * @var bool
	 */
	protected static $checking_payment = false;

	/**
	 * Registration constructor.
	 *
	 * @param string   $public_key
	 * @param string   $secret_key
	 * @param string   $gateway_mode
	 * @param Billing  $billing
	 * @param Products $products
	 */
	public function __construct( $public_key, $secret_key, $gateway_mode, $billing, $products ) {
		$this->public_key   = $public_key;
		$this->secret_key   = $secret_key;
		$this->gateway_mode = $gateway_mode;
		$this->billing      = $billing;
		$this->products     = $products;
	}

	/**
	 * Init
	 */
	public function init() {
		add_filter( 'wpum_get_account_page_tabs', array( $this, 'register_account_tab' ) );
		add_action( 'wpum_account_page_content_billing', array( $this, 'account_tab_content' ) );
		add_action( 'template_redirect', array( $this, 'unsubscribed_redirect' ) );
		add_filter( 'user_has_cap', array( $this, 'restrict_unpaid_capabilities' ), 10, 4 );
		add_filter( 'wpum_user_access_roles', array( $this, 'withhold_unpaid_access_roles' ), 10, 2 );
		add_action( 'added_user_meta', array( $this, 'flush_payment_status' ), 10, 2 );
		add_action( 'updated_user_meta', array( $this, 'flush_payment_status' ), 10, 2 );
		add_action( 'deleted_user_meta', array( $this, 'flush_payment_status' ), 10, 2 );

		add_action( 'wp_ajax_wpum_stripe_manage_billing', array( $this, 'handle_manage_billing' ) );
		add_action( 'wp_ajax_wpum_stripe_checkout', array( $this, 'handle_checkout' ) );

		add_action( 'template_redirect', array( $this, 'handle_download_invoice' ) );
		add_action( 'wpum_account_page_content', array( $this, 'render_payment_message' ), 9 );
	}

	/**
	 * Redirect users who aren't subscribed or paid
	 */
	public function unsubscribed_redirect() {
		if ( ! is_user_logged_in() || current_user_can( 'manage_options' ) ) {
			return;
		}

		global $post;

		$account_id = wpum_get_core_page_id( 'account' );

		$allowed_pages = array();
		if ( $account_id ) {
			$allowed_pages[] = $account_id;
		}

		$allowed_pages = apply_filters( 'wpum_stripe_not_paid_allowed_pages', $allowed_pages );
		$allowed_pages = array_map( 'intval', $allowed_pages );

		if ( isset( $post ) && in_array( $post->ID, $allowed_pages, true ) ) {
			return;
		}

		if ( ! $this->owes_payment( get_current_user_id() ) ) {
			return;
		}

		wp_safe_redirect( $this->billing->getBillingURL() );
		exit;
	}

	/**
	 * Does the user still owe a payment: an inactive subscription, or an unpaid one-time plan?
	 *
	 * @param int $user_id
	 *
	 * @return bool
	 */
	public function owes_payment( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return false;
		}

		if ( ! isset( self::$owes_payment[ $user_id ] ) ) {
			self::$checking_payment = true;

			$user = new User( $user_id );
			$owes = $user->shouldBeSubscribed() ? ! $user->isSubscribed() : ! $user->isPaid();

			self::$checking_payment = false;

			self::$owes_payment[ $user_id ] = $owes;
		}

		return self::$owes_payment[ $user_id ];
	}

	/**
	 * Limit users who still owe a payment to reading, everywhere on the site.
	 * The front-end redirect only covers theme pages, not wp-admin, REST or AJAX.
	 *
	 * @param array    $allcaps
	 * @param array    $caps
	 * @param array    $args
	 * @param \WP_User $user
	 *
	 * @return array
	 */
	public function restrict_unpaid_capabilities( $allcaps, $caps, $args, $user ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundInExtendedClassBeforeLastUsed -- Matches the user_has_cap filter signature.
		if ( self::$checking_payment || empty( $user->ID ) || ! empty( $allcaps['manage_options'] ) ) {
			return $allcaps;
		}

		if ( ! apply_filters( 'wpum_stripe_restrict_unpaid_capabilities', true, $user ) ) {
			return $allcaps;
		}

		if ( ! $this->owes_payment( $user->ID ) ) {
			return $allcaps;
		}

		return array_intersect_key( $allcaps, array_flip( array( 'exist', 'read' ) ) );
	}

	/**
	 * Role-restricted content checks role membership, not capabilities, so
	 * withhold the roles too while a payment is outstanding.
	 *
	 * @param array    $roles
	 * @param \WP_User $user
	 *
	 * @return array
	 */
	public function withhold_unpaid_access_roles( $roles, $user ) {
		if ( empty( $user->ID ) || user_can( $user, 'manage_options' ) ) {
			return $roles;
		}

		if ( ! apply_filters( 'wpum_stripe_restrict_unpaid_capabilities', true, $user ) ) {
			return $roles;
		}

		return $this->owes_payment( $user->ID ) ? array() : $roles;
	}

	/**
	 * Forget a user's cached payment status when their meta changes, e.g. a plan is paid.
	 *
	 * @param int|array $meta_id
	 * @param int       $user_id
	 */
	public function flush_payment_status( $meta_id, $user_id ) {
		unset( self::$owes_payment[ (int) $user_id ] );
	}

	/**
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function register_account_tab( $tabs ) {
		$user = new User( get_current_user_id() );

		// Subscribers manage billing here, and unpaid one-time buyers pay here.
		if ( ! $user->shouldBeSubscribed() && $user->isPaid() ) {
			return $tabs;
		}

		$tabs['billing'] = array(
			'name'     => esc_html__( 'Billing', 'wp-user-manager' ),
			'priority' => - 1,
		);

		return $tabs;
	}

	/**
	 * Account content
	 *
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function account_tab_content() {
		ob_start();

		$header = __( 'Billing', 'wp-user-manager' );
		if ( 'test' === $this->gateway_mode ) {
			$header .= ' (Stripe is connected in Test Mode)';
		}

		WPUM()->templates
			->set_template_data( array( 'header' => apply_filters( 'wpum_stripe_account_billing_header', $header ) ) )
			->get_template_part( 'stripe/account/header' );

		$user = new User( get_current_user_id() );

		$shouldBeSubscribed = $user->shouldBeSubscribed();
		if ( $shouldBeSubscribed && ! $user->isSubscribed() ) {
			WPUM()->templates
				->set_template_data( array( 'message' => apply_filters( 'wpum_stripe_account_subscription_required_error_message', __( 'An active subscription is required.', 'wp-user-manager' ) ) ) )
				->get_template_part( 'messages/general', 'error' );
		}

		if ( ! $shouldBeSubscribed && ! $user->isPaid() ) {
			WPUM()->templates
				->set_template_data( array( 'message' => apply_filters( 'wpum_stripe_account_payment_required_error_message', __( 'Payment is required for access to the site.', 'wp-user-manager' ) ) ) )
				->get_template_part( 'messages/general', 'error' );
		}

		if ( $user->subscription && $user->subscription->onGracePeriod() ) {
			WPUM()->templates
				/* translators: %s the datetime the subscription will cancel. */
				->set_template_data( array( 'message' => apply_filters( 'wpum_stripe_account_subscription_cancelled_message', sprintf( __( 'Your plan will be canceled on <strong>%s</strong>.', 'wp-user-manager' ), mysql2date( 'F j, Y', $user->subscription->ends_at ) ), $user->subscription->ends_at ) ) )
				->get_template_part( 'messages/general', 'warning' );
		}

		do_action( 'wpum_stripe_account_after_notices', $user );

		if ( ( $shouldBeSubscribed && ( ! $user->subscription || ! $user->subscription->active() ) ) || ( ! $shouldBeSubscribed && ! $user->isPaid() ) ) {
			$plans_data = array(
				'products'       => $this->products->all(),
				'allowed_prices' => wpum_get_option( $this->gateway_mode . '_stripe_products', array() ),
			);
			WPUM()->templates
				->set_template_data( $plans_data )
				->get_template_part( 'stripe/account/plans' );
		}

		if ( ! $shouldBeSubscribed ) {
			echo ob_get_clean(); // phpcs:ignore

			return;
		}

		if ( $user->subscription && $user->subscription->active() ) {
			// Manage billing
			$plan = $this->products->get_by_plan( $user->subscription->plan_id );

			WPUM()->templates
				->set_template_data( array( 'plan' => $plan ) )
				->get_template_part( 'stripe/account/manage-billing' );
		}

		$invoices = ( new Invoices( $this->gateway_mode ) )->where( 'user_id', $user->ID );

		WPUM()->templates
			->set_template_data( array( 'invoices' => $invoices ) )
			->get_template_part( 'stripe/account/invoices' );

		echo ob_get_clean(); // phpcs:ignore
	}

	/**
	 * @return void
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function handle_download_invoice() {
		$id = filter_input( INPUT_GET, 'invoice_id', FILTER_VALIDATE_INT );
		if ( empty( $id ) ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		global $post;
		if ( ! $post || (int) wpum_get_core_page_id( 'account' ) !== $post->ID ) {
			return;
		}

		$invoice = ( new Invoices( $this->gateway_mode ) )->find( $id );

		if ( get_current_user_id() !== (int) $invoice->user_id ) {
			return;
		}

		Stripe::setApiKey( $this->secret_key );

		try {
			$stripe_invoice = StripeInvoice::retrieve( $invoice->invoice_id );
		} catch ( \Exception $e ) {
			return;
		}

		( new Invoice(
			$stripe_invoice,
			$invoice
		) )->download();
	}

	/**
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function handle_manage_billing() {
		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_UNSAFE_RAW );
		$nonce = sanitize_text_field( $nonce );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wpum-stripe-manage-billing' ) ) {
			wp_send_json_error( __( 'Unknown Error', 'wp-user-manager' ) );
		}

		$user = new User( get_current_user_id() );

		if ( empty( $user->subscription ) ) {
			wp_send_json_error( __( 'No subscription', 'wp-user-manager' ) );
		}

		$checkout = $this->billing->createStripePortalSession( $this->secret_key, $user->subscription->customer_id );

		if ( ! $checkout ) {
			wp_send_json_error( __( 'Error creating Stripe session', 'wp-user-manager' ) );
		}

		wp_send_json_success( $checkout->toArray() );
	}

	/**
	 * Handle checkout
	 */
	public function handle_checkout() {
		$plan_id = filter_input( INPUT_POST, 'plan', FILTER_UNSAFE_RAW );
		$plan_id = sanitize_text_field( $plan_id );

		if ( empty( $plan_id ) ) {
			wp_send_json_error( __( 'Unknown plan', 'wp-user-manager' ) );
		}

		$nonce = filter_input( INPUT_POST, 'nonce', FILTER_UNSAFE_RAW );
		$nonce = sanitize_text_field( $nonce );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wpum-stripe-plan-' . $plan_id ) ) {
			wp_send_json_error( __( 'Unknown Error', 'wp-user-manager' ) );
		}

		$user = new User( get_current_user_id() );

		$form = $user->getFormRegisteredWith();

		$redirect = $this->get_redirect_after_account_payment( $plan_id, $form );

		$checkout_id = $this->billing->createStripeCheckoutSession( 'test' === $this->gateway_mode, $user, $plan_id, $redirect );

		if ( ! $checkout_id ) {
			$error = __( 'There has been an issue when registering, please contact the site owner', 'wp-user-manager' );
			wp_send_json_error( '<div class="wpum-message error">' . $error . '</div>' );
		}

		wp_send_json_success( array( 'id' => $checkout_id ) );
	}

	/**
	 * @param string $plan_id
	 * @param false  $form
	 *
	 * @return false|string
	 */
	public function get_redirect_after_account_payment( $plan_id, $form = false ) {
		$account_url = get_permalink( wpum_get_core_page_id( 'account' ) );
		$billing_url = $this->billing->getBillingURL();

		$return_url = $account_url;
		$product    = $this->products->get_by_plan( $plan_id );
		if ( $product->is_recurring() ) {
			$return_url = $billing_url;
		}

		if ( ! $form ) {
			return add_query_arg( array( 'payment' => 'success' ), $return_url );
		}

		$redirect_page = $form->get_setting( 'registration_redirect' );
		if ( $redirect_page ) {
			$return_url = get_permalink( $redirect_page[0] );
		}

		return apply_filters( 'wpum_registration_form_redirect', $return_url, $form );
	}

	/**
	 * Render payment message
	 */
	public function render_payment_message() {
		$payment = filter_input( INPUT_GET, 'payment', FILTER_UNSAFE_RAW );
		$payment = sanitize_text_field( $payment );
		if ( 'success' !== $payment ) {
			return;
		}

		ob_start();

		WPUM()->templates
			->set_template_data( array( 'message' => apply_filters( 'wpum_account_payment_success_message', esc_html__( 'Payment successfully completed.', 'wp-user-manager' ) ) ) )
			->get_template_part( 'messages/general', 'success' );

		echo ob_get_clean(); // phpcs:ignore
	}
}
