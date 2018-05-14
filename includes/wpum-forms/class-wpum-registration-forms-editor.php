<?php
/**
 * Handles the registration forms editor.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Registration_Forms_Editor {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_ajax_wpum_get_registration_forms', [ $this, 'get_forms' ] );
		add_action( 'wp_ajax_wpum_get_registration_form', [ $this, 'get_form' ] );
		add_action( 'wp_ajax_wpum_save_registration_form', [ $this, 'save_form' ] );
		add_action( 'wp_ajax_wpum_save_registration_form_settings', [ $this, 'save_form_settings' ] );
	}

	/**
	 * Add the registration forms editor link to the admin dashboard menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Registration Forms Editor', 'wp-user-manager' ),
			esc_html__( 'Registration forms', 'wp-user-manager' ),
			'manage_options',
			'wpum-registration-forms',
			[ $this, 'display_registration_forms_editor' ]
		);
	}

	/**
	 * Registration forms editor page. Display is handled by Vuejs.
	 *
	 * @return void
	 */
	public function display_registration_forms_editor() {
		echo '<div class="wrap"><div id="wpum-registration-forms-editor"></div></div>';
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if( $screen->base == 'users_page_wpum-registration-forms' ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-registration-forms-editor', 'http://localhost:8080/registration-forms-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_register_script( 'wpum-registration-forms-editor',  WPUM_PLUGIN_URL . 'dist/static/js/registration-forms-editor.js' , array(), WPUM_VERSION, true );
			}

			if( ! $is_vue_dev ) {
				wp_enqueue_script( 'wpum-vue-manifest' );
				wp_enqueue_script( 'wpum-vue-vendor' );
				wp_enqueue_style( 'wpum-registration-forms-editor-css', WPUM_PLUGIN_URL . 'dist/static/css/registration-forms-editor.css' , array(), WPUM_VERSION );
			}

			wp_enqueue_script( 'wpum-registration-forms-editor' );
			wp_enqueue_style( 'wpum-registration-forms-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css' , array(), WPUM_VERSION );

			$js_variables = [
				'labels'                => $this->get_labels(),
				'ajax'                  => admin_url( 'admin-ajax.php' ),
				'pluginURL'             => WPUM_PLUGIN_URL,
				'getFormsNonce'         => wp_create_nonce( 'wpum_get_registration_forms' ),
				'getFormNonce'          => wp_create_nonce( 'wpum_get_registration_form' ),
				'saveFormNonce'         => wp_create_nonce( 'wpum_save_registration_form' ),
				'saveFormSettingsNonce' => wp_create_nonce( 'wpum_save_registration_form_settings' )
			];

			wp_localize_script( 'wpum-registration-forms-editor', 'wpumRegistrationFormsEditor', $js_variables );

		}

	}

	/**
	 * Setup the labels for translation.
	 *
	 * @return void
	 */
	private function get_labels() {

		$labels = [
			'page_title'             => esc_html__( 'WP User Manager Registration Forms Editor', 'wp-user-manager' ),
			'table_name'             => esc_html__( 'Form name', 'wp-user-manager' ),
			'table_fields'           => esc_html__( 'Fields', 'wp-user-manager' ),
			'table_default'          => esc_html__( 'Default', 'wp-user-manager' ),
			'table_role'             => esc_html__( 'Registration role', 'wp-user-manager' ),
			'table_not_found'        => esc_html__( 'No registration forms have been found.', 'wp-user-manager' ),
			'table_default_tooltip'  => esc_html__( 'The default registration form cannot be deleted.', 'wp-user-manager' ),
			'table_customize'        => esc_html__( 'Customize fields', 'wp-user-manager' ),
			'page_back'              => esc_html__( 'Return to the registration forms list', 'wp-user-manager' ),
			'editor_available_title' => esc_html__( 'Available fields', 'wp-user-manager' ),
			'editor_available_desc'  => esc_html__( 'To add a field to this form, drag it into the container on the right. To remove a field, place it back here.', 'wp-user-manager' ),
			'table_field_name'       => esc_html__( 'Field name', 'wp-user-manager' ),
			'editor_used_fields'     => esc_html__( 'Add fields here to use them in this registration form. Drag fields up and down to change their order within the form.', 'wp-user-manager' ),
			'editor_drag'            => esc_html__( 'This form does not have any fields yet. Drag and drop fields here.', 'wp-user-manager' ),
			'success'                => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
			'error'                  => esc_html__( 'Something went wrong no changes saved.', 'wp-user-manager' ),
			'settings'               => esc_html__( 'Settings', 'wp-user-manager' ),
			'role_label'             => esc_html__( 'Registration role', 'wp-user-manager' ),
			'save'                   => esc_html__( 'Save changes', 'wp-user-manager' ),
			'role_desc'              => esc_html__( 'Select the user role that will be assigned to users upon successfull registration.', 'wp-user-manager' )
		];

		return $labels;

	}

	/**
	 * Retrieve the list of registration forms.
	 *
	 * @return void
	 */
	public function get_forms() {

		check_ajax_referer( 'wpum_get_registration_forms', 'nonce' );

		if( current_user_can( 'manage_options' ) && is_admin() ) {

			$registration_forms = WPUM()->registration_forms->get_forms();
			$forms              = [];

			foreach ( $registration_forms as $form ) {
				$forms[ $form->get_ID() ] = [
					'name'    => $form->get_name(),
					'default' => $form->is_default(),
					'role'    => $form->get_role(),
					'count'   => $form->get_fields_count()
				];
			}

			if( is_array( $forms ) && ! empty( $forms ) ) {
				wp_send_json_success( $forms );
			} else {
				$this->send_json_error();
			}

		} else {
			$this->send_json_error();
		}

	}

	/**
	 * Retrieve a single registration form given an ID.
	 *
	 * @return void
	 */
	public function get_form() {

		check_ajax_referer( 'wpum_get_registration_form', 'nonce' );

		if( current_user_can( 'manage_options' ) && is_admin() ) {

			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

			if( $form_id ) {

				$form = WPUM()->registration_forms->get( $form_id );
				$form = new WPUM_Registration_Form( $form->id );

				wp_send_json_success(
					[
						'name'             => $form->get_name(),
						'available_fields' => $this->get_available_fields( $form_id ),
						'stored_fields'    => $this->get_stored_fields( $form_id ),
						'selected_role'    => $form->get_meta( 'role' ),
						'allowed_roles'    => wpum_get_roles( true )
					]
				 );

			} else {

				$this->send_json_error();

			}

		} else {

			$this->send_json_error();

		}

	}

	/**
	 * Get all the fields used within a form.
	 *
	 * @param string $form_id
	 * @return array
	 */
	private function get_stored_fields( $form_id ) {

		if( ! $form_id ) {
			return;
		}

		$fields = [];

		$form = new WPUM_Registration_Form( $form_id );

		if( $form->exists() ) {

			$stored_fields = $form->get_meta( 'fields' );

			if( is_array( $stored_fields ) && ! empty( $stored_fields ) ) {
				foreach ( $stored_fields as $field ) {

					$stored_field = new WPUM_Field( $field );

					if( $stored_field->exists() ) {
						$fields[] = [
							'id'   => $stored_field->get_ID(),
							'name' => $stored_field->get_name()
						];
					}

				}
			}

		}

		return $fields;

	}

	/**
	 * Get fields available to be used within a registration form.
	 *
	 * @return array
	 */
	private function get_available_fields( $form_id ) {

		$fields = [];

		$available_fields = WPUM()->fields->get_fields( [
			'orderby' => 'fields_order',
			'order'   => 'ASC'
		] );

		$non_allowed_fields = [
			'user_avatar',
			'user_cover',
			'user_nickname',
			'user_displayname'
		];

		// Get fields already been used.
		$form          = new WPUM_Registration_Form( $form_id );
		$stored_fields = $form->get_meta( 'fields' );

		foreach ( $available_fields as $field ) {

			if( ! empty( $field->get_primary_id() ) && in_array( $field->get_primary_id(), $non_allowed_fields ) ) {
				continue;
			}

			if( in_array( $field->get_ID(), $stored_fields ) ) {
				continue;
			}

			$fields[] = [
				'id'   => $field->get_ID(),
				'name' => $field->get_name(),
			];
		}

		return $fields;

	}

	/**
	 * Save fields for this form to the database.
	 *
	 * @return void
	 */
	public function save_form() {

		check_ajax_referer( 'wpum_save_registration_form', 'nonce' );

		if( current_user_can( 'manage_options' ) && is_admin() ) {

			$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ) : false;
			$fields  = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) && ! empty( $_POST['fields'] ) ? $_POST['fields'] : false;

			if( $form_id ) {
				$registration_form = new WPUM_Registration_Form( $form_id );
				$fields_to_save    = [];

				if( $registration_form->exists() ) {
					foreach( $fields as $field ) {
						$fields_to_save[] = absint( $field['id'] );
					}
				}

				if( ! empty( $fields_to_save ) ) {
					$registration_form->update_meta( 'fields', $fields_to_save );
					wp_send_json_success();
				}

			}

		} else {

			$this->send_json_error();

		}

	}

	/**
	 * Save settings of the form.
	 *
	 * @return void
	 */
	public function save_form_settings() {

		check_ajax_referer( 'wpum_save_registration_form_settings', 'nonce' );

		if( current_user_can( 'manage_options' ) && is_admin() ) {

			$form_id = isset( $_POST['form_id'] ) ? absint( $_POST['form_id'] ):        false;
			$role    = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ): false;

			if( $form_id ) {

				$form = new WPUM_Registration_Form( $form_id );

				if( $form->exists() && get_role( $role ) && $role !== 'administrator' ) {

					$form->update_meta( 'role', $role );
					wp_send_json_success();

				} else {
					$this->send_json_error();
				}

			} else {
				$this->send_json_error();
			}

		} else {
			$this->send_json_error();
		}

	}

	/**
	 * Send and error back to the ajax request.
	 *
	 * @return void
	 */
	private function send_json_error() {
		wp_send_json_error( null, 403 );
	}

}

$wpum_registration_forms_editor = new WPUM_Registration_Forms_Editor;
