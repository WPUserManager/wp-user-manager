<?php
/**
 * Handles the WPUM profile privacy account form.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM_Form_Privacy
 */
class WPUM_Form_Privacy extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'privacy';

	/**
	 * Determine if there's a referrer.
	 *
	 * @var mixed
	 */
	protected $referrer;

	/**
	 * Stores static instance of class.
	 *
	 * @access protected
	 * @var WPUM_Form_Privacy The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Returns static instance of class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Holds the currently logged in user object.
	 *
	 * @var object
	 */
	protected $user;

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$this->user = wp_get_current_user();

		add_action( 'wp', array( $this, 'process' ) );

		$this->steps = (array) apply_filters(
			'privacy_steps', array(
				'submit' => array(
					'name'     => esc_html__( 'Profile Privacy', 'wp-user-manager' ),
					'view'     => array( $this, 'submit' ),
					'handler'  => array( $this, 'submit_handler' ),
					'priority' => 10,
				),
			)
		);

		$this->sort_set_steps();

	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$password_fields = array(
			'privacy' => array(
				'hide_profile_guests'  => array(
					'label'    => esc_html__( 'Hide my profile from guests', 'wp-user-manager' ),
					'type'     => 'checkbox',
					'required' => false,
					'priority' => 0,
					'value'    => \WPUM\carbon_get_user_meta( get_current_user_id(), 'hide_profile_guests' ),
				),
				'hide_profile_members' => array(
					'label'    => esc_html__( 'Hide my profile from members', 'wp-user-manager' ),
					'type'     => 'checkbox',
					'required' => false,
					'priority' => 1,
					'value'    => \WPUM\carbon_get_user_meta( get_current_user_id(), 'hide_profile_members' ),
				),
			),
		);

		$this->fields = apply_filters( 'privacy_form_fields', $password_fields );
	}

	/**
	 * Show the form.
	 *
	 * @return void
	 */
	public function submit() {

		$this->init_fields();

		$data = array(
			'form'      => $this->form_name,
			'action'    => $this->get_action(),
			'fields'    => $this->get_fields( 'privacy' ),
			'step'      => $this->get_step(),
			'step_name' => $this->steps[ $this->get_step_key( $this->get_step() ) ]['name'],
		);

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'privacy' );

	}

	/**
	 * Handle submission of the form.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function submit_handler() {

		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			$nonce = filter_input( INPUT_POST, 'privacy_nonce' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'verify_privacy_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_privacy'] ) ) {
				return;
			}

			if ( ! is_user_logged_in() ) {
				return;
			}

			if ( ! $this->user ) {
				return;
			}

			$return = $this->validate_fields( $values );
			if ( is_wp_error( $return ) ) {
				throw new Exception( $return->get_error_message() );
			}

			$user = wp_get_current_user();

			if ( $user instanceof WP_User ) {

				$user_id = $user->ID;

				foreach ( $values['privacy'] as $key => $value ) {
					if ( '1' === $value ) {
						$value = true;
					}

					carbon_set_user_meta( $user_id, $key, $value );
				}

				$redirect = get_permalink();
				$tab      = get_query_var( 'tab' );
				$redirect = rtrim( $redirect, '/' ) . '/' . $tab;
				$redirect = add_query_arg(
					array(
						'updated' => 'success',
					),
					$redirect
				);

				wp_safe_redirect( $redirect );
				exit;

			} else {
				throw new Exception( esc_html__( 'Something went wrong while updating your details.', 'wpum-custom-fields' ) );
			}
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'privacy_submit' );
			return;
		}

	}

}
