<?php
/**
 * Handles the WPUM registration form.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Form_Registration extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'registration';

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
	 * @var WPUM_Form_Register The single instance of the class
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
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'process' ) );

		add_filter( 'submit_wpum_form_validate_fields', [ $this, 'validate_password' ], 10, 4 );
		add_filter( 'submit_wpum_form_validate_fields', [ $this, 'validate_username' ], 10, 4 );
		add_filter( 'submit_wpum_form_validate_fields', [ $this, 'validate_honeypot' ], 10, 4 );

		$this->steps  = (array) apply_filters( 'registration_steps', array(
			'submit' => array(
				'name'     => esc_html__( 'Registration Details' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10
			),
			'done' => array(
				'name'     => esc_html__( 'Done' ),
				'view'     => false,
				'handler'  => array( $this, 'done' ),
				'priority' => 30
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
	 * Make sure the password is a strong one.
	 *
	 * @param boolean $pass
	 * @param array $fields
	 * @param array $values
	 * @param string $form
	 * @return mixed
	 */
	public function validate_password( $pass, $fields, $values, $form ) {

		if( $form == $this->form_name && isset( $values['register']['user_password'] ) ) {

			$password_1      = $values['register']['user_password'];
			$containsLetter  = preg_match('/[A-Z]/', $password_1 );
			$containsDigit   = preg_match('/\d/', $password_1 );
			$containsSpecial = preg_match('/[^a-zA-Z\d]/', $password_1 );

			if( ! $containsLetter || ! $containsDigit || ! $containsSpecial || strlen( $password_1 ) < 8 ) {
				return new WP_Error( 'password-validation-error', esc_html__( 'Password must be at least 8 characters long and contain at least 1 number, 1 uppercase letter and 1 special character.' ) );
			}

		}

		return $pass;

	}

	/**
	 * Make sure the chosen username is not part of the excluded list.
	 *
	 * @param boolean $pass
	 * @param array $fields
	 * @param array $values
	 * @param string $form
	 * @return mixed
	 */
	public function validate_username( $pass, $fields, $values, $form ) {

		if( $form == $this->form_name && isset( $values['register']['username'] ) ) {
			if( wpum_get_option('exclude_usernames') && array_key_exists( $values['register']['username'] , wpum_get_disabled_usernames() ) ) {
				return new WP_Error( 'nickname-validation-error', __( 'This username cannot be used.' ) );
			}
		}

		return $pass;

	}

	/**
	 * Validate the honeypot field.
	 *
	 * @param boolean $pass
	 * @param array $fields
	 * @param array $values
	 * @param string $form
	 * @return mixed
	 */
	public function validate_honeypot( $pass, $fields, $values, $form ) {

		if( $form == $this->form_name && isset( $values['register']['robo'] ) ) {
			if( ! empty( $values['register']['robo'] ) ) {
				return new WP_Error( 'honeypot-validation-error', esc_html__( 'Failed honeypot validation.' ) );
			}
		}

		return $pass;

	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$this->fields = [ 'register' => $this->get_registration_fields() ];

	}

	/**
	 * Retrieve the registration form from the database.
	 *
	 * @return void
	 */
	private function get_registration_form() {

		$form = WPUM()->registration_forms->get_forms();
		$form = $form[0];

		return $form;

	}

	/**
	 * Retrieve the registration form fields.
	 *
	 * @return array
	 */
	private function get_registration_fields() {

		$fields = [];
		$registration_form = $this->get_registration_form();

		if( $registration_form->exists() ) {

			$stored_fields = $registration_form->get_meta( 'fields' );

			if( is_array( $stored_fields ) && ! empty( $stored_fields ) ) {
				foreach ( $stored_fields as $field ) {

					$field = new WPUM_Field( $field );

					if( $field->exists() ) {
						$fields[ $this->get_parsed_id( $field->get_name(), $field->get_primary_id() ) ] = array(
							'label'       => $field->get_name(),
							'type'        => $field->get_type(),
							'required'    => $field->get_meta( 'required' ),
							'placeholder' => $field->get_meta( 'placeholder' ),
							'description' => $field->get_description(),
							'priority'    => 0,
							'primary_id'  => $field->get_primary_id()
						);
					}

				}
			}

			$fields['robo'] = [
				'label'       => esc_html__( 'If you\'re human leave this blank:' ),
				'type'        => 'text',
				'required'    => false,
				'priority'    => 0,
			];

		}

		return apply_filters( 'wpum_get_registration_fields', $fields );

	}

	/**
	 * Retrieve a name value for the form by replacing whitespaces with underscores
	 * and make everything lower case.
	 *
	 * If it's a primary field, get the primary id instead.
	 *
	 * @param string $name
	 * @param string $nicename
	 * @return void
	 */
	private function get_parsed_id( $name, $nicename ) {

		if( ! empty( $nicename ) ) {
			return str_replace(' ', '_', strtolower( $nicename ) );
		}

		return str_replace(' ', '_', strtolower( $name ) );
	}

	/**
	 * Detect wether to use a username or email to register a new account.
	 * Scan for fields within the registration form and check which ones are available.
	 *
	 * If the username field is available we'll always use this.
	 * If only the email field is available, we'll use the email as username.
	 *
	 * If no username or email field, we'll show an error.
	 *
	 * @return void
	 */
	private function get_register_by() {

		$by = false;

		$registered_fields = $this->get_fields( 'register' );

		if( is_array( $registered_fields ) && ! empty( $registered_fields ) ) {
			// Bail if no email field.
			if( ! isset( $registered_fields['user_email'] ) ) {
				return false;
			}
			if( isset( $registered_fields['username'] ) ) {
				$by = 'username';
			} else if( isset( $registered_fields['user_email'] ) ) {
				$by = 'email';
			}
		}

		return $by;

	}

	/**
	 * Display the first step of the registration form.
	 *
	 * @param array $atts
	 * @return void
	 */
	public function submit( $atts ) {

		$this->init_fields();
		$register_with = $this->get_register_by();

		$data = [
			'form'    => $this->form_name,
			'action'  => $this->get_action(),
			'fields'  => $this->get_fields( 'register' ),
			'step'    => $this->get_step(),
		];

		if( $register_with ) {

			WPUM()->templates
				->set_template_data( $data )
				->get_template_part( 'forms/form', 'registration' );

			WPUM()->templates
				->set_template_data( $atts )
				->get_template_part( 'action-links' );

		} else {

			WPUM()->templates
				->set_template_data( [ 'message' => esc_html__( 'The registration form cannot be used because either a username or email field is required to process registrations. Please edit the form and add at least the email field.' ) ] )
				->get_template_part( 'messages/general', 'error' );

		}

	}

	/**
	 * Process the registration form.
	 *
	 * @return void
	 */
	public function submit_handler() {

		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			if( ! wp_verify_nonce( $_POST['registration_nonce'], 'verify_registration_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_registration'] ) ) {
				return;
			}

			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Detect what we're going to use to register the new account.
			$register_with = $this->get_register_by();
			$username      = '';

			if( $register_with == 'username' ) {
				$username = $values['register']['username'];
			} else if( $register_with == 'email' ) {
				$username = $values['register']['user_email'];
			}

			// Detect if we're going to generate a password or use the one provided by the guest.
			$password = '';
			if( isset( $values['register']['user_password'] ) && ! empty( $values['register']['user_password'] ) ) {
				$password = $values['register']['user_password'];
			} else {
				$password = wp_generate_password(  12, true, true );
			}

			$new_user_id = wp_create_user( $username, $password, $values['register']['user_email'] );

			if( is_wp_error( $new_user_id ) ) {
				throw new Exception( $new_user_id->get_error_message() );
			}

			$new_user_id = wp_update_user( [
				'ID'          => $new_user_id,
				'user_url'    => isset( $values['register']['user_website'] ) ? $values['register']['user_website']:     false,
				'first_name'  => isset( $values['register']['user_firstname'] ) ? $values['register']['user_firstname']: false,
				'last_name'   => isset( $values['register']['user_lastname'] ) ? $values['register']['user_lastname']:   false,
				'description' => isset( $values['register']['user_description'] ) ? $values['register']['user_description']: false,
			] );

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}

	}

}
