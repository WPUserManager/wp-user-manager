<?php
/**
 * Handles the WPUM password recovery form.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
	 * Get things started.
	 */
	public function __construct() {

		add_action( 'wp', array( $this, 'process' ) );
		add_filter( 'submit_wpum_form_validate_fields', [ $this, 'validate_username_or_email' ], 10, 4 );

		$this->steps  = (array) apply_filters( 'password_reset_steps', array(
			'submit' => array(
				'name'     => esc_html__( 'Password recovery details request' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10
			),
			'sent' => array(
				'name'     => esc_html__( 'Instructions sent' ),
				'view'     => array( $this, 'instructions_sent' ),
				'handler'  => false,
				'priority' => 11
			),
			'reset' => array(
				'name'     => esc_html__( 'Reset password' ),
				'view'     => array( $this, 'reset' ),
				'handler'  => array( $this, 'reset_handler' ),
				'priority' => 12
			),
			'done' => array(
				'name'     => esc_html__( 'Done' ),
				'view'     => array( $this, 'done' ),
				'handler'  => array( $this, 'done_handler' ),
				'priority' => 12
			)
		) );

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

		$this->fields = apply_filters( 'password_recover_form_fields', array(
			'user' => array(
				'username_email' => array(
					'label'       => __( 'Username or email', 'wpum' ),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1
				),
			),
			'password' => array(
				'password' => array(
					'label'       => __( 'New password', 'wpum' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1
				),
				'password_2' => array(
					'label'       => __( 'Re-enter new password', 'wpum' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 2
				),
			),
		) );

		// If we're on the first step. We disable the password fields temporarily.
		if( $this->step === 0 ) {
			unset( $this->fields['password'] );
		}

	}

	/**
	 * Validate the requested username or email.
	 *
	 * @param boolean $pass
	 * @param array $fields
	 * @param array $values
	 * @param string $form
	 * @return void
	 */
	public function validate_username_or_email( $pass, $fields, $values, $form ) {

		if( $form == 'password-recovery' ) {
			$username = sanitize_text_field( $values['user']['username_email'] );
			if( is_email( $username ) && !email_exists( $username ) || !is_email( $username ) && !username_exists( $username ) )
				return new WP_Error( 'username-validation-error', esc_html__( 'A user with this username or email does not exist. Please check your entry and try again.', 'wpum' ) );
		}

		return $pass;

	}

	/**
	 * Show the password recovery form first step.
	 *
	 * @return void
	 */
	public function submit() {

		$this->init_fields();

		$data = [
			'form'   => $this->form_name,
			'action' => $this->get_action(),
			'fields' => $this->get_fields( 'user' ),
			'step'   => $this->get_step()
		];

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'password-recovery' );

	}

	/**
	 * Verify the user exists and if it does, send the recovery email.
	 *
	 * @return void
	 */
	public function submit_handler() {
		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			if( ! wp_verify_nonce( $_POST['password_recovery_nonce'], 'verify_password_recovery_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_password_recovery'] ) ) {
				return;
			}

			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			$username = $values['user']['username_email'];
			$user     = false;

			// Retrieve the user from the DB.
			if( is_email( $username ) ) {
				$user = get_user_by( 'email', $username );
			} else {
				$user = get_user_by( 'login', $username );
			}

			if( $user instanceof WP_User ) {

				// Generate a new password reset key for the selected user.
				$password_reset_key = get_password_reset_key( $user );

				// Now send an email to the user.
				if( $password_reset_key ) {

					$password_reset_email = wpum_get_email( 'password_recovery_request' );

					$emails = new WPUM_Emails;
					$emails->__set( 'user_id', $user->data->ID );
					$emails->__set( 'heading', $password_reset_email['title'] );
					$emails->__set( 'password_reset_key', $password_reset_key );

					if( is_array( $password_reset_email ) ) {
						$email   = $user->data->user_email;
						$subject = $password_reset_email['subject'];
						$message = $password_reset_email['content'];
						$emails->send( $email, $subject, $message );
					}

				}

			} else {
				throw new Exception( esc_html__( 'Something went wrong.' ) );
			}

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}
	}

	public function instructions_sent() {

		echo 'yup';

	}

}
