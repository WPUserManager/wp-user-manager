<?php
/**
 * Handles the Stripe registration
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUserManager\Stripe\Controllers\Products;
use WPUserManager\Stripe\Models\User;

/**
 * Registration
 */
class Registration {

	/**
	 * @var string
	 */
	protected $public_key;

	/**
	 * @var string
	 */
	protected $secret_key;

	/**
	 * @var bool
	 */
	protected $test_mode;

	/**
	 * @var Billing
	 */
	protected $billing;

	/**
	 * @var Products
	 */
	protected $products;

	/**
	 * @var array
	 */
	protected $allowed_plans;

	/**
	 * Plan validated for the registration being processed in this request.
	 *
	 * @var string|null
	 */
	protected $validated_plan_id;

	/**
	 * Plans the form offered for the registration being processed in this request.
	 *
	 * @var array
	 */
	protected $validated_allowed_plans = array();

	/**
	 * Registration constructor.
	 *
	 * @param string   $public_key
	 * @param string   $secret_key
	 * @param bool     $test_mode
	 * @param array    $billing
	 * @param Products $products
	 */
	public function __construct( $public_key, $secret_key, $test_mode, $billing, $products ) {
		$this->public_key    = $public_key;
		$this->secret_key    = $secret_key;
		$this->test_mode     = $test_mode;
		$this->billing       = $billing;
		$this->products      = $products;
		$this->allowed_plans = wpum_get_option( ( $this->test_mode ? 'test' : 'live' ) . '_stripe_products', array() );
	}

	/**
	 * Start it up
	 */
	public function init() {
		add_filter( 'wpum_registration_edit_form_settings_sections', array( $this, 'register_settings' ) );
		add_filter( 'wpum_get_registration_fields', array( $this, 'inject_registration_fields' ), 10, 2 );
		add_action( 'wpum_before_registration_end', array( $this, 'save_plan_after_registration' ), 10, 3 );
		add_action( 'wpum_after_existing_registration', array( $this, 'save_plan' ), 10, 3 );
		add_filter( 'submit_wpum_form_validate_fields', array( $this, 'validate_plan' ), 20, 4 );
		add_action( 'user_register', array( $this, 'record_plan_on_user_creation' ) );
		add_action( 'wp_ajax_wpum_stripe_register', array( $this, 'handle_register' ) );
		add_action( 'wp_ajax_nopriv_wpum_stripe_register', array( $this, 'handle_register' ) );
		add_filter( 'wpum_registered_settings_sections', array( $this, 'register_registration_settings_tab' ) );
	}

	/**
	 * @param array $sections
	 *
	 * @return array
	 */
	public function register_registration_settings_tab( $sections ) {
		$sections['registration']['payment'] = __( 'Payment', 'wp-user-manager' );

		return $sections;
	}

	/**
	 * Register registration form settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		$new_settings = array(
			'payment' => array(
				array(
					'id'       => 'stripe_plan_id',
					'name'     => __( 'Stripe Product', 'wp-user-manager' ),
					'desc'     => __( 'Take payment at registration for this Stripe product. Selecting multiple products will allow the user to choose at registration.', 'wp-user-manager' ),
					'type'     => 'multiselect',
					'multiple' => true,
					'options'  => $this->products->get_plans( $this->allowed_plans ),
				),
			),
		);

		return array_merge_recursive( $settings, $new_settings );
	}

	/**
	 * @param array                   $fields
	 * @param \WPUM_Registration_Form $registration_form
	 *
	 * @return array
	 */
	public function inject_registration_fields( $fields, $registration_form ) {
		$price_ids = $registration_form->get_setting( 'stripe_plan_id' );

		if ( empty( $price_ids ) ) {
			return $fields;
		}

		$plans = $this->products->get_plans( $this->allowed_plans );

		$options = array();
		foreach ( $plans as $plan ) {
			if ( in_array( $plan['value'], $price_ids, true ) ) {
				$options[ $plan['value'] ] = $plan['label'];
			}
		}

		$fields['wpum_stripe_plan'] = array(
			'label'       => apply_filters( 'wpum_stripe_registration_label', '', $registration_form ),
			'type'        => 'radio',
			'required'    => true,
			'options'     => $options,
			'description' => $this->test_mode ? 'Stripe is connected in Test Mode' : '',
			'priority'    => 9998,
		);

		return $fields;
	}

	/**
	 * @param \WPUM_Form $form
	 *
	 * @return string
	 */
	public function get_registration_redirect( $form ) {
		$form = $form->get_registration_form();

		$redirect_default = wpum_get_registration_redirect();
		if ( (bool) $form->get_setting( 'after_registration_form' ) ) {
			// Successful, the success message now.
			$referer  = isset( $_POST['_wp_http_referer'] ) ? $_POST['_wp_http_referer'] : '';  // phpcs:ignore
			$redirect = home_url( $referer );
			$redirect = add_query_arg( array( 'updated' => 'success' ), $redirect );

			$redirect_default = $redirect;
		}

		$redirect_page = $form->get_setting( 'registration_redirect' );
		if ( $redirect_page ) {
			$redirect_page = get_permalink( $redirect_page[0] );
		}

		if ( ! $redirect_page ) {
			$redirect_page = apply_filters( 'wpum_registration_form_redirect', $redirect_default, $form );
		}

		if ( ! $redirect_page ) {
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$redirect_page = $_SERVER['HTTP_REFERER']; // phpcs:ignore
			} else {
				$redirect_page = get_permalink( wpum_get_core_page_id( 'register' ) );
			}
		}

		return add_query_arg( array( 'registration' => 'success' ), $redirect_page );
	}

	/**
	 * Reject a registration whose plan isn't one of the plans the form offers.
	 * Runs before the account is created.
	 *
	 * @param bool|\WP_Error $pass
	 * @param array          $fields
	 * @param array          $values
	 * @param string         $form
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_plan( $pass, $fields, $values, $form ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Matches the validate fields filter signature.
		if ( is_wp_error( $pass ) ) {
			return $pass;
		}

		foreach ( (array) $fields as $group_key => $group_fields ) {
			if ( ! isset( $group_fields['wpum_stripe_plan'] ) ) {
				continue;
			}

			$options = isset( $group_fields['wpum_stripe_plan']['options'] ) ? (array) $group_fields['wpum_stripe_plan']['options'] : array();
			$allowed = array_map( 'strval', array_keys( $options ) );
			$plan_id = isset( $values[ $group_key ]['wpum_stripe_plan'] ) ? (string) $values[ $group_key ]['wpum_stripe_plan'] : '';

			if ( '' === $plan_id || ! in_array( $plan_id, $allowed, true ) ) {
				return new \WP_Error( 'stripe-plan-validation-error', __( 'Please select a valid plan.', 'wp-user-manager' ) );
			}

			$this->validated_plan_id       = $plan_id;
			$this->validated_allowed_plans = $allowed;
		}

		return $pass;
	}

	/**
	 * Record the plan as soon as the account exists, so a failure later in
	 * registration can't leave a paid form's account without plan data.
	 *
	 * @param int $user_id
	 */
	public function record_plan_on_user_creation( $user_id ) {
		if ( empty( $this->validated_plan_id ) ) {
			return;
		}

		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );

		$this->store_plan( $user_id, $this->validated_plan_id, $this->validated_allowed_plans );
	}

	/**
	 * @param int             $new_user_id
	 * @param array           $values
	 * @param \WPUM_Form|null $form
	 */
	public function save_plan( $new_user_id, $values = array(), $form = null ) {
		if ( ! empty( $this->validated_plan_id ) ) {
			$this->store_plan( $new_user_id, $this->validated_plan_id, $this->validated_allowed_plans );

			return;
		}

		// Nothing was validated in this request, so only accept a plan the form offers.
		if ( empty( $_POST['wpum_stripe_plan'] ) || ! $form ) { // phpcs:ignore
			return;
		}

		$plan_id = sanitize_text_field( $_POST['wpum_stripe_plan'] ); // phpcs:ignore
		$allowed = $this->get_form_plan_ids( $form );
		if ( ! in_array( $plan_id, $allowed, true ) ) {
			return;
		}

		$this->store_plan( $new_user_id, $plan_id, $allowed );
	}

	/**
	 * @param int    $user_id
	 * @param string $plan_id
	 * @param array  $allowed_plans
	 *
	 * @return bool
	 */
	protected function store_plan( $user_id, $plan_id, $allowed_plans ) {
		$product = $this->products->get_by_plan( $plan_id, $allowed_plans );
		if ( ! $product ) {
			return false;
		}

		$user = new User( $user_id );
		$user->setPlanMeta( $product->to_array() );

		return true;
	}

	/**
	 * @param int        $new_user_id
	 * @param array      $values
	 * @param \WPUM_Form $form
	 */
	public function save_plan_after_registration( $new_user_id, $values, $form ) {
		$this->save_plan( $new_user_id, $values, $form );

		update_user_meta( $new_user_id, 'wpum_form_id', $form->get_ID() );
	}

	/**
	 * Handle the registration via AJAX
	 */
	public function handle_register() {
		if ( empty( $_POST['data'] ) ) { // phpcs:ignore
			$this->json_error( __( 'Missing data', 'wp-user-manager' ) );
		}

		parse_str( $_POST['data'], $data ); // phpcs:ignore

		foreach ( $data as $key => $value ) {
			$_POST[ $key ] = $value;
		}

		$form = WPUM()->forms->load_posted_form( $data['wpum_form'] );

		if ( ! $form ) {
			$this->json_error( __( 'Missing form', 'wp-user-manager' ) );
		}

		if ( empty( $_POST['wpum_stripe_plan'] ) ) { // phpcs:ignore
			$this->json_error();
		}

		if ( ! $this->is_plan_allowed_for_form( sanitize_text_field( $_POST['wpum_stripe_plan'] ), $form ) ) { // phpcs:ignore
			$this->json_error();
		}

		$user_id = $form->submit_handler();

		ob_start();
		$form->show_errors();
		$errors = ob_get_clean();

		if ( $errors ) {
			wp_send_json_error( $errors );
		}

		if ( empty( $user_id ) ) {
			$this->json_error();
		}

		$plan_id = sanitize_text_field( $_POST['wpum_stripe_plan'] ); // phpcs:ignore

		$redirect = $this->get_registration_redirect( $form );

		$user = new User( $user_id );

		$checkout_id = $this->billing->createStripeCheckoutSession( $this->test_mode, $user, $plan_id, $redirect );

		if ( ! $checkout_id ) {
			$this->json_error();
		}

		wp_send_json_success( array( 'id' => $checkout_id ) );
	}

	/**
	 * Check the submitted price ID is one of the plans configured on the registration form.
	 *
	 * @param string     $plan_id
	 * @param \WPUM_Form $form
	 *
	 * @return bool
	 */
	protected function is_plan_allowed_for_form( $plan_id, $form ) {
		return in_array( $plan_id, $this->get_form_plan_ids( $form ), true );
	}

	/**
	 * The Stripe price IDs configured on a form's registration form.
	 *
	 * @param \WPUM_Form $form
	 *
	 * @return array
	 */
	protected function get_form_plan_ids( $form ) {
		if ( ! is_object( $form ) || ! method_exists( $form, 'get_registration_form' ) ) {
			return array();
		}

		$registration_form = $form->get_registration_form();
		if ( ! $registration_form ) {
			return array();
		}

		return array_map( 'strval', array_filter( (array) $registration_form->get_setting( 'stripe_plan_id' ) ) );
	}

	/**
	 * @param null $error
	 */
	protected function json_error( $error = null ) {
		if ( empty( $error ) ) {
			$error = __( 'There has been an issue when registering, please contact the site owner', 'wp-user-manager' );
		}

		wp_send_json_error( '<div class="wpum-message error">' . $error . '</div>' );
	}
}
