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
	protected static $_instance = null;

	/**
	 * Returns static instance of class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
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

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( $_POST['step'], array_keys( $this->steps ) );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( $_GET['step'], array_keys( $this->steps ) );
		}

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
				'hide_profile_guests' => array(
					'label'    => esc_html__( 'Hide my profile from guests', 'wp-user-manager' ),
					'type'     => 'checkbox',
					'required' => false,
					'priority' => 0,
					'value'    => carbon_get_user_meta( get_current_user_id(), 'hide_profile_guests' ),
				),
				'hide_profile_members' => array(
					'label'    => esc_html__( 'Hide my profile from members', 'wp-user-manager' ),
					'type'     => 'checkbox',
					'required' => false,
					'priority' => 1,
					'value'    => carbon_get_user_meta( get_current_user_id(), 'hide_profile_members' ),
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

		$data = [
			'form'      => $this->form_name,
			'action'    => $this->get_action(),
			'fields'    => $this->get_fields( 'privacy' ),
			'step'      => $this->get_step(),
			'step_name' => $this->steps[ $this->get_step_key( $this->get_step() ) ]['name'],
		];

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'privacy' );

	}

	/**
	 * Handle submission of the form.
	 *
	 * @return void
	 */
	public function submit_handler() {

		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			if ( ! wp_verify_nonce( $_POST['privacy_nonce'], 'verify_privacy_form' ) ) {
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

			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			$user = wp_get_current_user();

			if ( $user instanceof WP_User ) {

				$user_id = $user->ID;

				foreach ( $values['privacy'] as $key => $value ) {
						if ( $value == '1' ) {
							$value = true;
						}

						carbon_set_user_meta( $user_id, $key, $value );
				}

				$redirect = get_permalink();
				$tab      = get_query_var( 'tab' );
				$redirect = rtrim( $redirect, '/' ) . '/' . $tab;
				$redirect = add_query_arg(
					[
						'updated' => 'success',
					],
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
