<?php
/**
 * Handles the WPUM own password changing for for currently logged in users.
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
 * WPUM_Form_Password
 */
class WPUM_Form_Password extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'password';

	/**
	 * Determine if there's a referrer.
	 *
	 * @var mixed
	 */
	protected $referrer;

	/**
	 * Stores static instance of class.
	 *
	 * @var WPUM_Form_Password The single instance of the class
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
		add_filter( 'submit_wpum_form_validate_fields', array( $this, 'validate_password' ), 10, 4 );

		$this->steps = (array) apply_filters(
			'password_change_steps', array(
				'submit' => array(
					'name'     => esc_html__( 'Change password', 'wp-user-manager' ),
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
			'password' => array(
				'password'        => array(
					'label'       => esc_html__( 'Password', 'wp-user-manager' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 0,
				),
				'password_repeat' => array(
					'label'       => esc_html__( 'Repeat password', 'wp-user-manager' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1,
				),
			),
		);

		if ( wpum_get_option( 'current_password' ) ) {
			$password_fields['password']['current_password'] = array(
				'label'       => __( 'Current Password', 'wp-user-manager' ),
				'type'        => 'password',
				'required'    => true,
				'placeholder' => '',
				'priority'    => - 1,
			);
		}

		$this->fields = apply_filters( 'password_change_form_fields', $password_fields );
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
			'fields'    => $this->get_fields( 'password' ),
			'step'      => $this->get_step(),
			'step_name' => $this->steps[ $this->get_step_key( $this->get_step() ) ]['name'],
		);

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'password' );

	}

	/**
	 * Make sure the password is a strong one and matches the confirmation.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 * @return mixed
	 */
	public function validate_password( $pass, $fields, $values, $form ) {

		if ( $form === $this->form_name && isset( $values['password']['password'] ) ) {

			$password_current = $values['password']['current_password'];
			$password_1       = $values['password']['password'];
			$password_2       = $values['password']['password_repeat'];

			if ( wpum_get_option( 'current_password' ) ) {
				$user = wp_get_current_user();
				if ( $user && ! wp_check_password( $password_current, $user->data->user_pass, $user->ID ) ) {
					return new WP_Error( 'password-validation-wrongcurrent', esc_html__( 'Error: incorrect current password.', 'wp-user-manager' ) );
				}
			}

			$strong_password_check = $this->validate_strong_password( $password_1 );
			if ( is_wp_error( $strong_password_check ) ) {
				return $strong_password_check;
			}

			if ( $password_1 !== $password_2 ) {
				return new WP_Error( 'password-validation-nomatch', esc_html__( 'Error: passwords do not match.', 'wp-user-manager' ) );
			}
		}

		return $pass;

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

			$nonce = filter_input( INPUT_POST, 'password_change_nonce' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'verify_password_change_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_password'] ) ) {
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

			$updated_user_id = wp_update_user(
				array(
					'ID'        => $this->user->ID,
					'user_pass' => $values['password']['password'],
				)
			);

			/**
			 * Hook: allow developers to hook after the user changes his password from the account page.
			 *
			 * @param string $user_id the user id number.
			 * @param array $values all values submitted through the form.
			 */
			do_action( 'wpum_after_user_password_change', $this->user->ID, $values );

			if ( is_wp_error( $updated_user_id ) ) {
				throw new Exception( $updated_user_id->get_error_message() );
			} else {

				$active_tab = get_query_var( 'tab' );
				if ( empty( $active_tab ) ) {
					$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );

					$active_tab = $tab ? $tab : 'password';
				}
				$redirect = get_permalink();
				if ( ! $redirect ) {
					$redirect = get_permalink( wpum_get_core_page_id( 'account' ) );
				}
				$redirect = rtrim( $redirect, '/' ) . '/password';
				$redirect = add_query_arg(
					array(
						'password-updated' => 'success',
						'tab'              => $active_tab,
					), $redirect
				);

				wp_safe_redirect( $redirect );
				exit;

			}
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'password_change_submit' );
			return;
		}

	}

}
