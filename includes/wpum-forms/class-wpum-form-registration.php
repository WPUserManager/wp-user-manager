<?php
/**
 * Handles the WPUM registration form.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	 * @var WPUM_Form_Registration The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Store the role this form is going to use.
	 *
	 * @var string
	 */
	protected $role = null;

	/**
	 * @var WPUM_Registration_Form
	 */
	protected $registration_form;

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
		add_filter( 'submit_wpum_form_validate_fields', [ $this, 'validate_role' ], 10, 4 );
		add_action( 'wpum_registration_form_field', [ $this, 'render_registration_form_fields' ], 10, 2 );

		$this->steps = (array) apply_filters(
			'registration_steps',
			array(
				'submit' => array(
					'name'     => esc_html__( 'Registration Details', 'wp-user-manager' ),
					'view'     => array( $this, 'submit' ),
					'handler'  => array( $this, 'submit_handler' ),
					'priority' => 10,
				),
				'done'   => array(
					'name'     => false,
					'view'     => false,
					'handler'  => array( $this, 'done' ),
					'priority' => 30,
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
	 * Make sure the password is a strong one.
	 *
	 * @param bool $pass
	 * @param array $fields
	 * @param array $values
	 * @param string $form
	 *
	 * @return bool|WP_Error
	 */
	public function validate_password( $pass, $fields, $values, $form ) {

		if ( $form == $this->form_name && isset( $values['register']['user_password'] ) ) {

			$strong_password_check = $this->validate_strong_password( $values['register']['user_password'] );
			if ( is_wp_error( $strong_password_check ) ) {
				return $strong_password_check;
			}
		}

		return $pass;
	}

	/**
	 * Make sure the chosen username is not part of the excluded list.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 * @return mixed
	 */
	public function validate_username( $pass, $fields, $values, $form ) {

		if ( $form == $this->form_name && isset( $values['register']['username'] ) ) {
			if ( wpum_get_option( 'exclude_usernames' ) && array_key_exists( strtolower( $values['register']['username'] ), wpum_get_disabled_usernames() ) ) {
				return new WP_Error( 'nickname-validation-error', __( 'This username cannot be used.', 'wp-user-manager' ) );
			}
		}

		return $pass;

	}

	/**
	 * Validate the honeypot field.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 * @return mixed
	 */
	public function validate_honeypot( $pass, $fields, $values, $form ) {

		if ( $form == $this->form_name && isset( $values['register']['robo'] ) ) {
			if ( ! empty( $values['register']['robo'] ) ) {
				return new WP_Error( 'honeypot-validation-error', esc_html__( 'Failed honeypot validation.', 'wp-user-manager' ) );
			}
		}

		return $pass;

	}

	/**
	 * Validate role on submission.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 * @return mixed
	 */
	public function validate_role( $pass, $fields, $values, $form ) {
		$registration_form = $this->get_registration_form();

		$allow_role_select = $registration_form->get_setting( 'allow_role_select' );
		if ( empty( $allow_role_select ) ) {
			return $pass;
		}

		if ( $form == $this->form_name && isset( $values['register']['role'] ) ) {
			$role_field     = $values['register']['role'];
			$selected_roles = array_flip( $registration_form->get_setting( 'register_roles' ) );
			if ( ! array_key_exists( $role_field, $selected_roles ) ) {
				return new WP_Error( 'role-validation-error', __( 'Select a valid role from the list.', 'wp-user-manager' ) );
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
	 * @return WPUM_Registration_Form
	 */
	public function get_registration_form() {
		if ( $this->registration_form ) {
			return $this->registration_form;
		}

		$form = WPUM()->registration_forms->get_forms();
		$this->registration_form = $form[0];

		return $this->registration_form;
	}

	/**
	 * Retrieve the registration form fields.
	 *
	 * @return array
	 */
	protected function get_registration_fields() {

		$fields            = [];
		$registration_form = $this->get_registration_form();

		if ( $registration_form->exists() ) {

			$this->role    = $registration_form->get_role_key();
			$stored_fields = $registration_form->get_fields();

			if ( is_array( $stored_fields ) && ! empty( $stored_fields ) ) {
				foreach ( $stored_fields as $key => $field ) {

					$field = new WPUM_Field( $field );

					if ( $field->exists() ) {
						$data = array(
							'label'       => $field->get_name(),
							'type'        => $field->get_type(),
							'required'    => $field->get_meta( 'required' ),
							'placeholder' => $field->get_meta( 'placeholder' ),
							'description' => $field->get_description(),
							'priority'    => $key,
							'primary_id'  => $field->get_primary_id(),
							'options'     => $this->get_custom_field_dropdown_options( $field ),
							'template'    => $field->get_parent_type(),
						);

						$data = array_merge( $data, $field->get_field_data() );

						$fields[ $this->get_parsed_id( $field->get_name(), $field->get_primary_id(), $field ) ] = $data;
					}
				}
			}

			// Add honeypot validation field.
			$fields['robo'] = [
				'label'    => esc_html__( 'If you\'re human leave this blank:', 'wp-user-manager' ),
				'type'     => 'text',
				'required' => false,
				'priority' => 0,
			];

			if ( $registration_form->get_setting( 'allow_role_select' ) ) {
				$selected_roles = $registration_form->get_setting( 'register_roles', array() );

				$fields['role'] = array(
					'label'       => __( 'Select Role', 'wp-user-manager' ),
					'type'        => 'dropdown',
					'required'    => true,
					'options'     => wpum_get_allowed_user_roles( $selected_roles ),
					'description' => __( 'Select your user role', 'wp-user-manager' ),
					'priority'    => 9998,
					'value'       => get_option( 'default_role' ),
				);
			}

			// Add a terms field is enabled.
			if ( $registration_form->get_setting( 'enable_terms' ) ) {
				$terms_page      = $registration_form->get_setting( 'terms_page' );
				$fields['terms'] = array(
					'label'       => false,
					'type'        => 'checkbox',
					'description' => apply_filters( 'wpum_terms_text', sprintf( __( 'By registering to this website you agree to the <a href="%s" target="_blank">terms &amp; conditions</a>.', 'wp-user-manager' ), get_permalink( $terms_page[0] ) ) ),
					'required'    => true,
					'priority'    => 9999,
				);
			}

			// Add privacy policy checkbox if enabled in WP.
			if ( get_option( 'wp_page_for_privacy_policy' ) ) {
				$fields['privacy'] = array(
					'label'       => false,
					'type'        => 'checkbox',
					'description' => apply_filters( 'wpum_privacy_text', sprintf( __( 'I have read and accept the <a href="%1$s" target="_blank">privacy policy</a> and allow "%2$s" to collect and store the data I submit through this form.', 'wp-user-manager' ), get_permalink( get_option( 'wp_page_for_privacy_policy' ) ), get_bloginfo( 'name' ) ) ),
					'required'    => true,
					'priority'    => 9999,
				);
			}
		}

		return apply_filters( 'wpum_get_registration_fields', $fields, $registration_form );
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

		if ( is_array( $registered_fields ) && ! empty( $registered_fields ) ) {
			// Bail if no email field.
			if ( ! isset( $registered_fields['user_email'] ) && ! isset( $registered_fields['username'] ) ) {
				return false;
			}
			if ( isset( $registered_fields['username'] ) ) {
				$by = 'username';
			} elseif ( isset( $registered_fields['user_email'] ) ) {
				$by = 'email';
			}
		}

		return $by;

	}

	protected function get_submit_data() {
		return [
			'form'    => $this->form_name,
			'action'  => $this->get_action(),
			'fields'  => $this->get_fields( 'register' ),
			'step'    => $this->get_step(),
		];
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

		$data = $this->get_submit_data();

		if ( $register_with ) {

			WPUM()->templates
				->set_template_data( $data )
				->get_template_part( 'forms/form', 'registration' );

			WPUM()->templates
				->set_template_data( $atts )
				->get_template_part( 'action-links' );

		} else {

			$admin_url = admin_url( 'users.php?page=wpum-registration-forms#/' );

			WPUM()->templates
				->set_template_data( [ 'message' => __( 'The registration form cannot be used because either a username or email field is required to process registrations. Please edit the form and add at least the email field.', 'wp-user-manager' ) . ' ' . '<a href="' . esc_url_raw( $admin_url ) . '">' . $admin_url . '</a>' ] )
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

			if ( ! wp_verify_nonce( $_POST['registration_nonce'], 'verify_registration_form' ) ) {
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

			if ( $register_with == 'username' ) {
				$username = $values['register']['username'];
			} elseif ( $register_with == 'email' ) {
				$username = $values['register']['user_email'];
			}

			// Detect if we're going to generate a password or use the one provided by the guest.
			$password = '';
			if ( isset( $values['register']['user_password'] ) && ! empty( $values['register']['user_password'] ) ) {
				$password = $values['register']['user_password'];
			} else {
				$password = wp_generate_password( 24, true, true );
			}

			$user_email = isset( $values['register']['user_email'] ) ? $values['register']['user_email'] : '';

			$new_user_id = wp_create_user( $username, $password, $user_email );

			if ( is_wp_error( $new_user_id ) ) {
				throw new Exception( $new_user_id->get_error_message() );
			}

			$new_user_id = wp_update_user(
				[
					'ID'          => $new_user_id,
					'user_url'    => isset( $values['register']['user_website'] ) ? $values['register']['user_website'] : false,
					'first_name'  => isset( $values['register']['user_firstname'] ) ? $values['register']['user_firstname'] : false,
					'last_name'   => isset( $values['register']['user_lastname'] ) ? $values['register']['user_lastname'] : false,
					'description' => isset( $values['register']['user_description'] ) ? $values['register']['user_description'] : false,
				]
			);

			$form = $this->get_registration_form();

			// Assign the role set into the registration form.
			if ( $form->get_setting( 'allow_role_select' ) && isset( $values['register']['role'] ) ) {
				$user = new WP_User( $new_user_id );
				$user->set_role( $values['register']['role'] );
			} else {
				$user = new WP_User( $new_user_id );
				$user->set_role( $this->role );
			}

			if ( isset( $values['register']['user_cover']['url'] ) ) {
				carbon_set_user_meta( $user->ID, 'user_cover', $values['register']['user_cover']['url'] );
				update_user_meta( $user->ID, '_user_cover_path', $values['register']['user_cover']['path'] );
			}

			if ( isset( $values['register']['user_avatar']['url'] ) ) {
				carbon_set_user_meta( $user->ID, 'current_user_avatar', $values['register']['user_avatar']['url'] );
				update_user_meta( $user->ID, '_current_user_avatar_path', $values['register']['user_avatar']['path'] );
			}

			// Allow developers to extend signup process.
			do_action( 'wpum_before_registration_end', $new_user_id, $values, $form );

			// Now send a confirmation email to the user.
			wpum_send_registration_confirmation_email( $new_user_id, $password );

			// Allow developers to extend signup process.
			do_action( 'wpum_after_registration', $new_user_id, $values, $form );

			// Automatically log a user in if enabled.
			$login_after_reg = $form->get_setting( 'login_after_registration' );
			$login_after_reg = empty( $login_after_reg ) ? false : $login_after_reg;
			$auto_login_user = apply_filters( 'wpum_auto_login_user_after_registration', $login_after_reg );
			if ( $auto_login_user ) {
				wpum_log_user_in( $new_user_id );
			}

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'registration_submit' );
			return;
		}

	}

	/**
	 * Get the redirect page URL
	 *
	 * @return string
	 */
	protected function get_redirect_page() {
		return wpum_get_registration_redirect();
	}

	/**
	 * Last step of the registration form.
	 * Redirect user to the selected page in the admin panel or a show a success message.
	 *
	 * @return void
	 */
	public function done() {
		$redirect_page = $this->get_redirect_page();

		if ( $redirect_page ) {
			wp_safe_redirect( $redirect_page );
			exit;
		} else {
			$registration_page = get_permalink( wpum_get_core_page_id( 'register' ) );
			$registration_page = add_query_arg( [ 'registration' => 'success' ], $registration_page );

			wp_safe_redirect( $registration_page );
			exit;
		}
	}

	/**
	 * Rendering built in form fields
	 *
	 */
	public function render_registration_form_fields($field, $key){
		$registered_groups = array_column(wpum_get_registered_field_types(), 'fields');
		$registered_fields = $registered_groups ? call_user_func_array('array_merge', $registered_groups) : [];
		$registered_types  = $registered_fields ? array_column($registered_fields, 'type') : [];

		if( in_array( $field['type'], $registered_types ) ){

			WPUM()->templates
				->set_template_data( [ 'field' => $field, 'key' => $key ] )
				->get_template_part( 'forms/form-registration-fields', 'field' );
		}
	}

}
