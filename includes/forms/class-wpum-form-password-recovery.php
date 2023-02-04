<?php
/**
 * Handles the WPUM password recovery form.
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
 * WPUM_Form_Password_Recovery
 */
class WPUM_Form_Password_Recovery extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'password-recovery';

	/**
	 * Stores static instance of class.
	 *
	 * @access protected
	 * @var WPUM_Form_Login The single instance of the class
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
	 * @return string
	 */
	public static function get_cookie() {
		return 'wpum-resetpass-' . COOKIEHASH;
	}

	/**
	 * Get things started.
	 */
	public function __construct() {

		add_action( 'wp', array( $this, 'process' ) );

		add_filter( 'submit_wpum_form_validate_fields', array( $this, 'validate_username_or_email' ), 10, 4 );
		add_filter( 'submit_wpum_form_validate_fields', array( $this, 'validate_passwords' ), 10, 4 );

		$this->steps = (array) apply_filters( 'password_reset_steps', array(
			'submit' => array(
				'name'     => esc_html__( 'Password recovery details request', 'wp-user-manager' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10,
			),
			'sent'   => array(
				'name'     => esc_html__( 'Instructions sent', 'wp-user-manager' ),
				'view'     => array( $this, 'instructions_sent' ),
				'handler'  => false,
				'priority' => 11,
			),
			'reset'  => array(
				'name'     => esc_html__( 'Reset password', 'wp-user-manager' ),
				'view'     => array( $this, 'reset' ),
				'handler'  => array( $this, 'reset_handler' ),
				'priority' => 12,
			),
			'done'   => array(
				'name'     => esc_html__( 'Done', 'wp-user-manager' ),
				'view'     => array( $this, 'done' ),
				'handler'  => false,
				'priority' => 13,
			),
		) );

		$this->sort_set_steps();

	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$authentication_method = wpum_get_option( 'login_method' );
		$username_label        = __( 'Username or Email Address' );
		if ( 'username' === $authentication_method ) {
			$username_label = __( 'Username', 'wp-user-manager' );
		}
		if ( 'email' === $authentication_method ) {
			$username_label = __( 'Email', 'wp-user-manager' );
		}

		$this->fields = apply_filters( 'password_recover_form_fields', array(
			'user'     => array(
				'username_email' => array(
					'label'       => $username_label,
					'type'        => 'text',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1,
				),
			),
			'password' => array(
				'password'   => array(
					'label'       => __( 'New password', 'wp-user-manager' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1,
				),
				'password_2' => array(
					'label'       => __( 'Re-enter new password', 'wp-user-manager' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 2,
				),
			),
		) );

		// If we're on the first step. We disable the password fields temporarily.
		// If we're on the reset step, we disable the user fields.
		if ( 0 === $this->step ) {
			unset( $this->fields['password'] );
		} elseif ( 2 === $this->step ) {
			unset( $this->fields['user'] );
		}

		if ( isset( $_GET['user_id'] ) && isset( $_GET['key'] ) && isset( $_GET['step'] ) && 'reset' === $_GET['step'] ) { // phpcs:ignore
			unset( $this->fields['user'] );
		}

	}

	/**
	 * Validate the requested username or email.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 *
	 * @return bool|WP_Error
	 */
	public function validate_username_or_email( $pass, $fields, $values, $form ) {

		if ( 'password-recovery' === $form && isset( $values['user']['username_email'] ) ) {
			$username = sanitize_text_field( $values['user']['username_email'] );
			if ( is_email( $username ) && ! email_exists( $username ) || ! is_email( $username ) && ! username_exists( $username ) ) {
				return new WP_Error( 'username-validation-error', esc_html__( 'A user with this username or email does not exist. Please check your entry and try again.', 'wp-user-manager' ) );
			}
		}

		return $pass;
	}

	/**
	 * Show the password recovery form first step.
	 *
	 * @param mixed $atts
	 *
	 * @return void
	 */
	public function submit( $atts ) {

		$this->init_fields();

		$data = array(
			'form'    => $this->form_name,
			'action'  => $this->get_action(),
			'fields'  => $this->get_fields( 'user' ),
			'step'    => $this->get_step(),
			'message' => apply_filters( 'wpum_lost_password_message', esc_html__( 'Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'wp-user-manager' ) ),
		);

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'password-recovery' );

		WPUM()->templates
			->set_template_data( $atts )
			->get_template_part( 'action-links' );

	}

	/**
	 * Verify the user exists and if it does, send the recovery email.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function submit_handler() {
		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			$nonce = filter_input( INPUT_POST, 'password_recovery_nonce' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'verify_password_recovery_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_password_recovery'] ) ) {
				return;
			}

			$return = $this->validate_fields( $values );
			if ( is_wp_error( $return ) ) {
				throw new Exception( $return->get_error_message() );
			}

			$username = $values['user']['username_email'];
			$user     = false;

			// Retrieve the user from the DB.
			if ( is_email( $username ) ) {
				$user = get_user_by( 'email', $username );
			} else {
				$user = get_user_by( 'login', $username );
			}

			if ( $user instanceof WP_User ) {

				// Generate a new password reset key for the selected user.
				$password_reset_key = get_password_reset_key( $user );

				// Now send an email to the user.
				if ( $password_reset_key ) {

					$password_reset_email = wpum_get_email( 'password_recovery_request', $user->data->ID );

					$emails = new WPUM_Emails();
					$emails->__set( 'user_id', $user->data->ID );
					$emails->__set( 'user_login', $user->data->user_login );
					$emails->__set( 'heading', $password_reset_email['title'] );
					$emails->__set( 'password_reset_key', $password_reset_key );

					if ( is_array( $password_reset_email ) ) {
						$email   = $user->data->user_email;
						$subject = $password_reset_email['subject'];
						$message = $password_reset_email['content'];
						$emails->send( $email, $subject, $message );
					}
				}
			} else {
				throw new Exception( esc_html__( 'Something went wrong.', 'wp-user-manager' ) );
			}

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'password_recovery_submit' );
			return;
		}
	}

	/**
	 * Display a success message after successfully requesting a new password.
	 *
	 * @return void
	 */
	public function instructions_sent() {

		$values = $this->get_posted_fields();

		$username = $values['user']['username_email'];

		if ( is_email( $username ) ) {
			$user = get_user_by( 'email', $username );
		} else {
			$user = get_user_by( 'login', $username );
		}

		$data = array(
			'email' => $user->data->user_email,
			'from'  => wpum_get_option( 'from_email' ),
		);

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'messages/password-reset', 'request-success' );

	}

	/**
	 * Display the password reset form.
	 *
	 * @return void
	 */
	public function reset() {

		$this->init_fields();

		$cookie_key = self::get_cookie();
		$cookie     = filter_input( INPUT_COOKIE, $cookie_key, FILTER_SANITIZE_STRING );

		if ( $cookie && 0 < strpos( $cookie, ':' ) ) {
			list( $rp_login, $verification_key ) = explode( ':', wp_unslash( $cookie ), 2 );

			$verify_key = check_password_reset_key( $verification_key, $rp_login );

			if ( is_wp_error( $verify_key ) ) {
				$data = array(
					'message' => esc_html__( 'The reset key is wrong or expired. Please check that you used the right reset link or request a new one.', 'wp-user-manager' ),
				);

				list( $rp_path ) = explode( '?', wp_unslash( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ) );

				setcookie( self::get_cookie(), ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

				WPUM()->templates->set_template_data( $data )->get_template_part( 'messages/general', 'error' );
			} else {

				$data = array(
					'form'    => $this->form_name,
					'action'  => $this->get_action(),
					'fields'  => $this->get_fields( 'password' ),
					'step'    => $this->get_step(),
					'message' => apply_filters( 'wpum_new_password_message', esc_html__( 'Enter a new password below.', 'wp-user-manager' ) ),
				);

				WPUM()->templates
					->set_template_data( $data )
					->get_template_part( 'forms/form', 'password-recovery' );

			}
		} else {

			$data = array(
				'message' => esc_html__( 'The link you followed may be broken. Please check that you used the right reset link or request a new one.', 'wp-user-manager' ),
			);

			WPUM()->templates
				->set_template_data( $data )
				->get_template_part( 'messages/general', 'error' );

		}

	}

	/**
	 * Validate the 2 passwords are the same and make sure they're safe enough.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 *
	 * @return bool|WP_Error
	 */
	public function validate_passwords( $pass, $fields, $values, $form ) {

		if ( 'password-recovery' === $form && isset( $values['password']['password'] ) && isset( $values['password']['password_2'] ) ) {

			$password_1 = $values['password']['password'];
			$password_2 = $values['password']['password_2'];

			if ( $password_1 !== $password_2 ) {
				return new WP_Error( 'password-validation-nomatch', esc_html__( 'Error: passwords do not match.', 'wp-user-manager' ) );
			}

			$strong_password_check = $this->validate_strong_password( $password_1 );
			if ( is_wp_error( $strong_password_check ) ) {
				return $strong_password_check;
			}
		}

		return $pass;
	}

	/**
	 * Finally reset the user password now.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function reset_handler() {

		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			$nonce = filter_input( INPUT_POST, 'password_recovery_nonce' );
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'verify_password_recovery_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_password_recovery'] ) ) { // phpcs:ignore
				return;
			}

			$return = $this->validate_fields( $values );
			if ( is_wp_error( $return ) ) {
				throw new Exception( $return->get_error_message() );
			}

			$password_1 = $values['password']['password'];
			$password_2 = $values['password']['password_2'];

			$cookie_key = self::get_cookie();
			$cookie     = filter_input( INPUT_COOKIE, $cookie_key, FILTER_SANITIZE_STRING );

			if ( $cookie && 0 < strpos( $cookie, ':' ) ) {
				list( $rp_login, $verification_key ) = explode( ':', wp_unslash( $cookie ), 2 );

				$verify_key = check_password_reset_key( $verification_key, $rp_login );

				if ( is_wp_error( $verify_key ) ) {
					throw new Exception( $verify_key->get_error_message() );
				}
			}

			if ( empty( $verify_key->ID ) ) {
				return;
			}

			$user_id = $verify_key->ID;

			wp_set_password( $password_1, $user_id );

			list( $rp_path ) = explode( '?', wp_unslash( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ) );

			setcookie( self::get_cookie(), ' ', time() - YEAR_IN_SECONDS, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

			/**
			 * Hook: allow developers to hook after the user recovers his password from the account page.
			 *
			 * @param int   $user_id the user id number.
			 * @param array $values all values submitted through the form.
			 */
			do_action( 'wpum_after_user_password_recovery', $user_id, $values );

			// Clear all user sessions.
			$sessions = WP_Session_Tokens::get_instance( $user_id );
			$sessions->destroy_all();

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'password_recovery_reset' );
			return;
		}

	}

	/**
	 * Display success message at the end.
	 *
	 * @return void
	 */
	public function done() {

		$data = array(
			// translators: %s login URL
			'message' => wp_kses_post( sprintf( __( 'Password successfully reset. <a href="%s">Login now &raquo;</a>', 'wp-user-manager' ), get_permalink( wpum_get_core_page_id( 'login' ) ) ) ),
		);

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'messages/general', 'success' );

	}

}
