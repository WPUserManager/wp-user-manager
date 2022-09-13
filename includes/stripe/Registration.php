<?php
/**
 * Handles the Stripe registration
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\WPUMStripe;

use WPUserManager\WPUMStripe\Controllers\Products;
use WPUserManager\WPUMStripe\Models\User;

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
	 * Registration constructor.
	 *
	 * @param string $public_key
	 * @param string $secret_key
	 * @param string $test_mode
	 * @param array  $billing
	 * @param array  $products
	 */
	public function __construct( $public_key, $secret_key, $test_mode, $billing, $products ) {
		$this->public_key = $public_key;
		$this->secret_key = $secret_key;
		$this->test_mode  = $test_mode;
		$this->billing    = $billing;
		$this->products   = $products;
	}

	/**
	 * Start it up
	 */
	public function init() {
		add_action( 'wpum_registered_settings', array( $this, 'register_settings' ) );

		add_filter( 'wpum_get_registration_fields', array( $this, 'inject_registration_fields' ), 10, 2 );

		add_action( 'wpum_before_registration_end', array( $this, 'save_plan' ), 10, 3 );
		add_action( 'wp_ajax_wpum_stripe_register', array( $this, 'handle_register' ) );
		add_action( 'wp_ajax_nopriv_wpum_stripe_register', array( $this, 'handle_register' ) );
	}

	/**
	 * Register registration form settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		$settings['registration'][] = array(
			'id'       => 'stripe_plan_id',
			'name'     => __( 'Stripe Product', 'wp-user-manager' ),
			'desc'     => __( 'Take payment at registration for this Stripe product. Selecting multiple products will allow the user to choose at registration.', 'wp-user-manager' ),
			'type'     => 'multiselect',
			'multiple' => true,
			'options'  => $this->products->get_plans(),
		);

		return $settings;
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

		$plans = $this->products->get_plans();

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
			'description' => '',
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
		$form          = $form->get_registration_form();
		$redirect_page = $form->get_setting( 'registration_redirect' );
		if ( $redirect_page ) {
			$redirect_page = get_permalink( $redirect_page[0] );
		}

		if ( ! $redirect_page ) {
			$redirect_page = apply_filters( 'wpum_registration_form_redirect', wpum_get_registration_redirect(), $form );
		}

		if ( ! $redirect_page ) {
			if ( isset( $_SERVER['HTTP_REFERER'] ) ) {
				$redirect_page = $_SERVER['HTTP_REFERER'];
			} else {
				$redirect_page = get_permalink( wpum_get_core_page_id( 'register' ) );
			}
			$redirect_page = add_query_arg( array( 'registration' => 'success' ), $redirect_page );
		}

		return $redirect_page;
	}

	public function save_plan( $new_user_id, $values, $form ) {
		if ( isset( $_POST['wpum_stripe_plan'] ) ) {
			$product = $this->products->get_by_plan( $_POST['wpum_stripe_plan'] );

			update_user_meta( $new_user_id, 'wpum_stripe_plan', $product->to_array() );
		}

		update_user_meta( $new_user_id, 'wpum_form_id', $form->get_ID() );
	}

	public function handle_register() {
		if ( empty( $_POST['data'] ) ) {
			wp_send_json_error( '<div class="wpum-message error">Missing data</div>' );
		}

		parse_str( $_POST['data'], $data );

		foreach ( $data as $key => $value ) {
			$_POST[ $key ] = $value;
		}

		$form = WPUM()->forms->load_posted_form();

		if ( ! $form ) {
			wp_send_json_error( '<div class="wpum-message error">Missing form</div>' );
		}

		$user_id = $form->submit_handler();

		ob_start();
		$form->show_errors();
		$errors = ob_get_clean();

		if ( $errors ) {
			wp_send_json_error( $errors );
		}

		$plan_id = $_POST['wpum_stripe_plan'];

		// TODO make sure this works for non-default registration form URLs
		$redirect = $this->get_registration_redirect( $form );

		$user = new User( $user_id );

		$checkout_id = $this->billing->createStripeCheckoutSession( $this->test_mode, $user, $plan_id, $redirect );

		if ( ! $checkout_id ) {
			$error = __( 'There has been an issue when registering, please contact the site owner', 'wp-user-manager' );
			wp_send_json_error( '<div class="wpum-message error">' . $error . '</div>' );
		}

		wp_send_json_success( array( 'id' => $checkout_id ) );
	}
}
