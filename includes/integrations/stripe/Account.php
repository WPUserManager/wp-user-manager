<?php


namespace WPUserManager\Stripe;

use WPUM\Stripe\Invoice as StripeInvoice;
use WPUM\Stripe\Stripe;
use WPUserManager\Stripe\Controllers\Invoices;
use WPUserManager\Stripe\Controllers\Products;
use WPUserManager\Stripe\Models\User;

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
	 * Registration constructor.
	 *
	 * @param $public_key
	 * @param $secret_key
	 * @param $gateway_mode
	 * @param $billing
	 * @param $products
	 */
	public function __construct( $public_key, $secret_key, $gateway_mode, $billing, $products ) {
		$this->public_key   = $public_key;
		$this->secret_key   = $secret_key;
		$this->gateway_mode = $gateway_mode;
		$this->billing      = $billing;
		$this->products     = $products;
	}

	public function init() {
		add_filter( 'wpum_get_account_page_tabs', array( $this, 'register_account_tab' ) );
		add_action( 'wpum_account_page_content_billing', array( $this, 'account_tab_content' ) );
		add_action( 'template_redirect', array( $this, 'unsubscribed_redirect' ) );

		add_action( 'wp_ajax_wpum_stripe_manage_billing', array( $this, 'handle_manage_billing' ) );
		add_action( 'wp_ajax_nopriv_wpum_stripe_manage_billing', array( $this, 'handle_manage_billing' ) );
		add_action( 'wp_ajax_wpum_stripe_checkout', array( $this, 'handle_checkout' ) );
		add_action( 'wp_ajax_nopriv_wpum_checkout', array( $this, 'handle_checkout' ) );

		add_action( 'template_redirect', array( $this, 'handle_download_invoice' ) );
		add_action( 'wpum_account_page_content', array( $this, 'render_payment_message' ), 9 );

	}

	function unsubscribed_redirect() {
		// TODO check we want to restrict content
		if ( ! is_user_logged_in() || current_user_can( 'administrator' ) ) {
			return;
		}

		global $post;

		$account_id = wpum_get_core_page_id( 'account' );

		$allowed_pages = array();
		if ( $account_id ) {
			$allowed_pages[] = $account_id;
		}

		$allowed_pages = apply_filters( 'wpum_stripe_not_paid_allowed_pages', $allowed_pages );

		if ( isset( $post ) && in_array( $post->ID, $allowed_pages ) ) {
			return;
		}

		// TODO check user should have subscription by looking at signed up plan
		$user = new User( get_current_user_id() );

		$shouldBeSubscribed = $user->shouldBeSubscribed();
		if ( $shouldBeSubscribed && $user->isSubscribed() ) {
			return;
		}

		if ( ! $shouldBeSubscribed && $user->isPaid() ) {
			return;
		}

		wp_redirect( $this->billing->getBillingURL() );
		exit;
	}

	public function register_account_tab( $tabs ) {
		$user = new User( get_current_user_id() );
		if ( ! $user->shouldBeSubscribed() || $user->isPaid() ) {
			return $tabs;
		}

		$tabs['billing'] = array(
			'name'     => esc_html__( 'Billing', 'wp-user-manager' ),
			'priority' => - 1,
		);

		return $tabs;
	}

	public function account_tab_content() {
		echo '<h2>' . apply_filters( 'wpum_stripe_account_billing_header', __( 'Billing', 'wp-user-manager' ) ) . '</h2>';

		$user = new User( get_current_user_id() );

		$shouldBeSubscribed = $user->shouldBeSubscribed();
		if ( $shouldBeSubscribed && ! $user->isSubscribed() ) {
			echo '<div class="wpum-message error">' . apply_filters( 'wpum_stripe_account_subscription_required_error_message', __( 'An active subscription is required.', 'wp-user-manager' ) ) . '</div>';
		}

		if ( ! $shouldBeSubscribed && ! $user->isPaid() ) {
			echo '<div class="wpum-message error">' . apply_filters( 'wpum_stripe_account_payment_required_error_message', __( 'Payment is required for access to the site.', 'wp-user-manager' ) ) . '</div>';
		}
		if ( $user->subscription && $user->subscription->active() && $user->subscription->onTrial() && ! $user->subscription->onGracePeriod() ) {
			echo '<div class="wpum-message info">' . apply_filters( 'wpum_stripe_account_free_trial_message', sprintf( __( 'After your free trial ends on <strong>%s</strong>, this plan will continue automatically.', 'wp-user-manager' ), mysql2date( __( 'F j, Y' ), $user->subscription->trial_ends_at ) ), $user->subscription->trial_ends_at ) . '</div>';
		}

		if ( $user->subscription && $user->subscription->onGracePeriod() ) {
			echo '<div class="wpum-message warning">' . apply_filters( 'wpum_stripe_account_subscription_cancelled_message', sprintf( __( 'Your plan will be canceled on <strong>%s</strong>.', 'wp-user-manager' ), mysql2date( __( 'F j, Y' ), $user->subscription->ends_at ) ), $user->subscription->ends_at ) . '</div>';
		}

		if ( ( $shouldBeSubscribed && ( ! $user->subscription || ! $user->subscription->active() ) ) || ( ! $shouldBeSubscribed && ! $user->isPaid() ) ) {
			echo '<h4>' . apply_filters( 'wpum_stripe_account_billing_plan_header', __( 'Select Plan', 'wp-user-manager' ) ) . '</h4>'; ?>

			<?php foreach ( $this->products->all() as $product ) : ?>
				<div class="wpum-row wpum-form">
					<div class="wpum-col-xs-3">
						<?php echo $product['name']; ?>
					</div>
					<div class="wpum-col-xs-3">
						<?php foreach ( $product['prices'] as $price_id => $price ) : ?>
							<strong><?php echo \WPUserManager\Stripe\Stripe::currencySymbol( $price['currency'] ) . number_format( $price['unit_amount'] / 100 ); ?></strong><?php echo isset( $price['recurring']['interval'] ) ? '/' . $price['recurring']['interval'] : ''; ?>
							<br>
						<?php endforeach; ?>
					</div>
					<div class="wpum-col-xs-3">
						<?php foreach ( $product['prices'] as $price_id => $price ) : ?>
							<button class="wpum-stripe-checkout button" data-plan-id="<?php echo $price_id; ?>">
								<?php echo apply_filters( 'wpum_stripe_account_billing_plan_button_label', __( 'Select Plan', 'wp-user-manager' ) ) . '</h4>'; ?>
							</button><br>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<?php
		}

		if ( ! $shouldBeSubscribed ) {
			return;
		}

		if ( $user->subscription && $user->subscription->active() ) {
			// TODO move to templates
			// Manage billing
			$plan = $this->products->get_by_plan( $user->subscription->plan_id );
			echo '<div class="wpum-form">';
			echo '<p>' . sprintf( __( 'You\'re currently on the %s plan.', 'wp-user-manager' ), $plan->name ) . '</p>';

			echo '<button id="wpum-stripe-manage-billing" class="button" style="margin-top: 1rem">' . __( 'Manage Billing', 'wp-user-manager' ) . '</button>';
			echo '</div>';
		}

		$invoices = ( new Invoices( $this->gateway_mode ) )->where( 'user_id', $user->ID );
		echo '<div class="wpum-form" style="margin-top: 2rem;">';
		echo '<h3>Invoices</h3>';
		if ( empty( $invoices ) ) {
			echo '<p> ' . __( 'You don\'t have any invoices yet.', 'wp-user-manager' ) . '</p></div>';
			return;
		}
		?>
		<table class="table mb-0">
			<tbody>
			<?php
			foreach ( $invoices as $invoice ) :
				if ( $invoice->total <= 0 ) {
					continue;
				}
				?>
				<tr>
					<td class="">
						<?php echo mysql2date( __( 'F j, Y' ), $invoice->created_at ); ?>
					</td>
					<td class="">
						<?php echo \WPUserManager\Stripe\Stripe::currencySymbol( $invoice->currency ); ?><?php echo number_format( $invoice->total ); ?>
					</td>
					<td class="text-right">
						<a href="<?php echo home_url( '/account/billing/?invoice_id=' . $invoice->id ); ?>">
							Download
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div>
		<?php

	}

	/**
	 * @return mixed|void
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
		if ( ! $post || $post->ID != wpum_get_core_page_id( 'account' ) ) {
			return;
		}

		$invoice = ( new Invoices( $this->gateway_mode ) )->find( $id );

		if ( $invoice->user_id != get_current_user_id() ) {
			return;
		}

		Stripe::setApiKey( $this->secret_key );

		try {
			$stripe_invoice = StripeInvoice::retrieve( $invoice->invoice_id );
		} catch ( \Exception $e ) {
			return;
		}

		return ( new Invoice(
			$stripe_invoice,
			$invoice
		) )->download();
	}

	public function handle_manage_billing() {
		// TODO nonce

		$user = new User( get_current_user_id() );

		if ( empty( $user->subscription ) ) {
			wp_send_json_error( 'No subscription' );
		}

		$checkout = $this->billing->createStripePortalSession( $this->secret_key, $user->subscription->customer_id );

		wp_send_json_success( $checkout->toArray() );
	}

	public function handle_checkout() {
		// TODO nonce
		$plan_id = $_POST['plan'];

		$user = new User( get_current_user_id() );

		$form = $user->getFormRegisteredWith();

		$redirect = $this->get_redirect_after_account_payment( $plan_id, $form );

		$checkout_id = $this->billing->createStripeCheckoutSession( $this->gateway_mode === 'test', $user, $plan_id, $redirect );

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

	public function render_payment_message() {
		$payment = filter_input( INPUT_GET, 'payment', FILTER_SANITIZE_STRING );
		if ( 'success' !== $payment ) {
			return;
		}

		ob_start();

		WPUM()->templates
			->set_template_data( array( 'message' => apply_filters( 'wpum_account_payment_success_message', esc_html__( 'Payment successfully completed.', 'wp-user-manager' ) ) ) )
			->get_template_part( 'messages/general', 'success' );

		echo ob_get_clean();
	}
}
