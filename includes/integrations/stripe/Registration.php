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
		add_action( 'wpum_registration_edit_form_settings_sections', array( $this, 'register_settings' ) );
		add_filter( 'wpum_get_registration_fields', array( $this, 'inject_registration_fields' ), 10, 2 );
		add_action( 'wpum_before_registration_end', array( $this, 'save_plan_after_registration' ), 10, 3 );
		add_action( 'wpum_after_existing_registration', array( $this, 'save_plan' ) );
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
			'required'    => false,
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
	 * @param int $new_user_id
	 */
	public function save_plan( $new_user_id ) {
		if ( isset( $_POST['wpum_stripe_plan'] ) ) { // phpcs:ignore
			$product = $this->products->get_by_plan( sanitize_text_field( $_POST['wpum_stripe_plan'] ) ); // phpcs:ignore

			$user = new User( $new_user_id );
			$user->setPlanMeta( $product->to_array() );
		};
	}

	/**
	 * @param int        $new_user_id
	 * @param array      $values
	 * @param \WPUM_Form $form
	 */
	public function save_plan_after_registration( $new_user_id, $values, $form ) {
		$this->save_plan( $new_user_id );

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

		if ( empty( $_POST['wpum_stripe_plan'] ) ) { // phpcs:ignore
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
	 * @param null $error
	 */
	protected function json_error( $error = null ) {
		if ( empty( $error ) ) {
			$error = __( 'There has been an issue when registering, please contact the site owner', 'wp-user-manager' );
		}

		wp_send_json_error( '<div class="wpum-message error">' . $error . '</div>' );
	}
}
