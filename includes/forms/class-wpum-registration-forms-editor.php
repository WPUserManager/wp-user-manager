<?php
/**
 * Handles the registration forms editor.
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
 * WPUM_Registration_Forms_Editor
 */
class WPUM_Registration_Forms_Editor {

	/**
	 * @var string
	 */
	protected $capability;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->capability = apply_filters( 'wpum_admin_pages_capability', 'manage_options' );
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_menu', array( $this, 'setup_menu_page' ), 9 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_wpum_get_registration_forms', array( $this, 'get_forms' ) );
		add_action( 'wp_ajax_wpum_get_registration_form', array( $this, 'get_form' ) );
		add_action( 'wp_ajax_wpum_update_registration_form', array( $this, 'update_form' ) );
		add_action( 'wp_ajax_wpum_save_registration_form', array( $this, 'save_form' ) );
		add_action( 'wp_ajax_wpum_save_registration_form_settings', array( $this, 'save_form_settings' ) );
		add_action( 'wp_ajax_wpum_get_registration_form_field', array( $this, 'get_form_field_data' ) );

		add_filter( 'wpum_form_settings_sanitize_text', array( $this, 'sanitize_text_field' ) );
		add_filter( 'wpum_form_settings_sanitize_textarea', array( $this, 'sanitize_textarea_field' ) );
		add_filter( 'wpum_form_settings_sanitize_radio', array( $this, 'sanitize_text_field' ) );
		add_filter( 'wpum_form_settings_sanitize_select', array( $this, 'sanitize_text_field' ) );
		add_filter( 'wpum_form_settings_sanitize_checkbox', array( $this, 'sanitize_checkbox_field' ) );
		add_filter( 'wpum_form_settings_sanitize_multiselect', array( $this, 'sanitize_multiple_field' ) );
		add_filter( 'wpum_form_settings_sanitize_multicheckbox', array( $this, 'sanitize_multiple_field' ) );
		add_filter( 'wpum_form_settings_sanitize_file', array( $this, 'sanitize_file_field' ) );

		// Object Caching hooks
		add_action( 'wpum_registration_form_insert', array( $this, 'delete_registration_forms_cache' ) );
		add_action( 'wpum_before_registration_form_delete', array( $this, 'delete_registration_forms_cache' ) );
		add_action( 'wpum_registration_form_duplicated', array( $this, 'delete_registration_forms_cache' ) );
	}

	/**
	 * Add the registration forms editor link to the admin dashboard menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'Registration Forms', 'wp-user-manager' ),
			esc_html__( 'Registration Forms', 'wp-user-manager' ),
			$this->capability,
			'wpum-registration-forms',
			array( $this, 'display_registration_forms_editor' )
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

		if ( 'users_page_wpum-registration-forms' === $screen->base ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV;

			if ( $is_vue_dev ) {
				$vue_dev_port = defined( 'WPUM_VUE_DEV_PORT' ) ? WPUM_VUE_DEV_PORT : '8080';
				wp_register_script( 'wpum-registration-forms-editor', 'http://localhost:' . $vue_dev_port . '/registration-forms-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_register_script( 'wpum-registration-forms-editor', WPUM_PLUGIN_URL . 'dist/static/js/registration-forms-editor.js', array(), WPUM_VERSION, true );
			}

			if ( ! $is_vue_dev ) {
				wp_enqueue_script( 'wpum-vue-manifest' );
				wp_enqueue_script( 'wpum-vue-vendor' );
				wp_enqueue_style( 'wpum-registration-forms-editor-css', WPUM_PLUGIN_URL . 'dist/static/css/registration-forms-editor.css', array(), WPUM_VERSION );
			}

			wp_enqueue_script( 'wpum-registration-forms-editor' );
			wp_enqueue_style( 'wpum-registration-forms-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css', array(), WPUM_VERSION );
			wp_enqueue_style( 'wpum-registration-forms-editor-ok', WPUM_PLUGIN_URL . 'vendor/wp-user-manager/wp-optionskit/dist/static/css/app.css', array(), WPUM_VERSION );

			$js_variables = array(
				'is_addon_installed'    => apply_filters( 'wpum_registration_forms_has_registration_forms_addon', false ),
				'labels'                => $this->get_labels(),
				'ajax'                  => admin_url( 'admin-ajax.php' ),
				'pluginURL'             => WPUM_PLUGIN_URL,
				'addon_url'             => 'https://wpusermanager.com/addons/registration-forms?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=registration-forms-editor',
				'getFormsNonce'         => wp_create_nonce( 'wpum_get_registration_forms' ),
				'getFormNonce'          => wp_create_nonce( 'wpum_get_registration_form' ),
				'saveFormNonce'         => wp_create_nonce( 'wpum_save_registration_form' ),
				'saveFormSettingsNonce' => wp_create_nonce( 'wpum_save_registration_form_settings' ),
				'nonce'                 => wp_create_nonce( 'wpum_update_registration_form' ),
				'delete_form_nonce'     => wp_create_nonce( 'wpum_delete_registration_form' ),
				'edit_form_sections'    => $this->form_edit_sections(),
			);

			wp_localize_script( 'wpum-registration-forms-editor', 'wpumRegistrationFormsEditor', $js_variables );
		}

	}

	/**
	 * Setup the labels for translation.
	 *
	 * @return array
	 */
	private function get_labels() {
		$labels = array(
			'confirm_delete'         => esc_html__( 'Confirm Form Deletion?', 'wp-user-manager' ),
			'modal_form_delete'      => esc_html__( 'You are about to delete the registration form:', 'wp-user-manager' ),
			'modal_delete'           => esc_html__( 'This action cannot be reversed. Are you sure you want to continue?', 'wp-user-manager' ),
			'page_title'             => esc_html__( 'Registration Forms', 'wp-user-manager' ),
			'table_name'             => esc_html__( 'Form Name', 'wp-user-manager' ),
			'table_fields'           => esc_html__( 'Fields', 'wp-user-manager' ),
			'table_default'          => esc_html__( 'Default', 'wp-user-manager' ),
			'table_role'             => esc_html__( 'Registration Role', 'wp-user-manager' ),
			'table_signup_total'     => esc_html__( 'Total Signups', 'wp-user-manager' ),
			'table_shortcode'        => esc_html__( 'Shortcode', 'wp-user-manager' ),
			'table_actions'          => esc_html__( 'Actions', 'wp-user-manager' ),
			'table_not_found'        => esc_html__( 'No registration forms have been found.', 'wp-user-manager' ),
			'table_add_form'         => esc_html__( 'Add New Form', 'wp-user-manager' ),
			'table_edit_form'        => esc_html__( 'Edit Form Name', 'wp-user-manager' ),
			'table_default_tooltip'  => esc_html__( 'The default registration form cannot be deleted.', 'wp-user-manager' ),
			'table_delete_form'      => esc_html__( 'Delete Form', 'wp-user-manager' ),
			'table_customize'        => esc_html__( 'Customize Form', 'wp-user-manager' ),
			'page_back'              => esc_html__( 'Return to the registration forms list', 'wp-user-manager' ),
			'editor_available_title' => esc_html__( 'Available Fields', 'wp-user-manager' ),
			'editor_current_title'   => esc_html__( 'Form Fields', 'wp-user-manager' ),
			'editor_available_desc'  => esc_html__( 'To add a field to this form, drag it into the container on the left. To remove a field, place it back here.', 'wp-user-manager' ),
			'table_field_name'       => esc_html__( 'Field name', 'wp-user-manager' ),
			'editor_used_fields'     => esc_html__( 'Add fields here to use them in this registration form. Drag fields up and down to change their order within the form.', 'wp-user-manager' ),
			'editor_drag'            => esc_html__( 'This form does not have any fields yet. Drag and drop fields here.', 'wp-user-manager' ),
			'success'                => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
			'error'                  => esc_html__( 'Something went wrong no changes saved.', 'wp-user-manager' ),
			'settings'               => esc_html__( 'Settings', 'wp-user-manager' ),
			'role_label'             => esc_html__( 'Registration role', 'wp-user-manager' ),
			'save'                   => esc_html__( 'Save Changes', 'wp-user-manager' ),
			'role_desc'              => esc_html__( 'Select the user role that will be assigned to users upon successfull registration.', 'wp-user-manager' ),
			'tooltip_form_name'      => esc_html__( 'Customize the name of the registration form.', 'wp-user-manager' ),
			'create_form'            => esc_html__( 'Create Form', 'wp-user-manager' ),
			// translators: %1$s Registration forms addon URL
			'premium_addon'          => sprintf( __( 'Create <a href="%1$s" target="_blank">unlimited registration forms</a>. The <a href="%1$s" target="_blank">Registration Forms</a> addon is required if you wish to add more forms.', 'wp-user-manager' ), 'https://wpusermanager.com/addons/registration-forms?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=registration-forms-editor' ),
			// translators: %1$s Registration forms addon URL
			'premium_addonstep'      => sprintf( __( 'Create <a href="%1$s" target="_blank">multi-step registration forms</a>. The <a href="%1$s" target="_blank">Registration Forms</a> addon is required if you wish to add steps to your forms.', 'wp-user-manager' ), 'https://wpusermanager.com/addons/registration-forms?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=registration-forms-editor' ),
			// translators: %1$s Registration forms addon URL
			'premium_addonhtml'      => sprintf( __( 'Add <a href="%1$s" target="_blank">custom HTML to your registration forms</a>. The <a href="%1$s" target="_blank">Registration Forms</a> addon is required if you wish to add HTML to your forms.', 'wp-user-manager' ), 'https://wpusermanager.com/addons/registration-forms?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=registration-forms-editor' ),
			'purchase'               => esc_html__( 'Purchase', 'wp-user-manager' ),
			'success_message'        => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
		);

		return $labels;
	}

	/**
	 * Retrieve the list of registration forms.
	 *
	 * @return void
	 */
	public function get_forms() {

		check_ajax_referer( 'wpum_get_registration_forms', 'nonce' );

		if ( current_user_can( $this->capability ) && is_admin() ) {

			$registration_forms = WPUM()->registration_forms->get_forms();
			$forms              = array();

			foreach ( $registration_forms as $form ) {
				$form_data = array(
					'id'      => $form->get_ID(),
					'name'    => $form->get_name(),
					'default' => $form->is_default(),
					'role'    => $form->get_role(),
					'count'   => $form->get_fields_count(),
				);

				$forms[] = apply_filters( 'wpum_get_registration_form_data_for_table', $form_data );
			}

			if ( is_array( $forms ) && ! empty( $forms ) ) {
				wp_send_json_success( $forms );
			} else {
				$this->send_json_error();
			}
		} else {
			$this->send_json_error();
		}

	}

	/**
	 * Update a form via ajax.
	 *
	 * @return void
	 */
	public function update_form() {
		check_ajax_referer( 'wpum_update_registration_form', 'nonce' );

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the registration form details.', 'wp-user-manager' ), 403 );
		}

		$form_id   = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
		$form_name = filter_input( INPUT_POST, 'form_name', FILTER_SANITIZE_STRING );
		$form_name = $form_name ? sanitize_text_field( $form_name ) : false;

		if ( $form_id && $form_name ) {

			$data = apply_filters( 'wpum_registration_form_update', array(
				'name' => $form_name,
			), $form_id );

			$updated_form = WPUM()->registration_forms->update( $form_id, $data );

			$this->delete_registration_forms_cache();

		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the registration form details.', 'wp-user-manager' ), 403 );
		}

		wp_send_json_success( array(
			'id'   => $form_id,
			'name' => $form_name,
		) );
	}

	/**
	 * Retrieve a single registration form given an ID.
	 *
	 * @return void
	 */
	public function get_form() {

		check_ajax_referer( 'wpum_get_registration_form', 'nonce' );

		if ( current_user_can( $this->capability ) && is_admin() ) {

			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

			if ( $form_id ) {

				$form = WPUM()->registration_forms->get( $form_id );
				$form = new WPUM_Registration_Form( $form->id );

				$all_labels     = ( new WPUM_Options_Panel() )->register_labels( array() );
				$settings       = $form->get_settings_options_by_section();
				$settings_model = $form->get_settings_model();
				foreach ( $settings as $key => $setting ) {
					foreach ( $setting as $field_key => $field ) {
						$settings[ $key ][ $field_key ]['current']    = isset( $settings_model[ $field['id'] ] ) ? $settings_model[ $field['id'] ] : '';
						$settings[ $key ][ $field_key ]['all_labels'] = $all_labels;
					}
				}

				wp_send_json_success( array(
					'name'             => $form->get_name(),
					'available_fields' => $this->get_available_fields( $form_id ),
					'stored_fields'    => $this->get_stored_fields( $form_id ),
					'settings'         => $settings,
					'settings_model'   => $settings_model,
				) );

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

		if ( ! $form_id ) {
			return;
		}

		$fields = array();

		$form = new WPUM_Registration_Form( $form_id );

		if ( $form->exists() ) {

			$stored_fields = $form->get_fields();

			if ( is_array( $stored_fields ) && ! empty( $stored_fields ) ) {
				foreach ( $stored_fields as $field ) {
					$field_data   = array();
					$stored_field = new WPUM_Field( $field );

					if ( $stored_field->exists() ) {
						$icon = isset( $stored_field->field_type->icon ) ? $stored_field->field_type->icon : 'dashicons-editor-justify';

						$field_data = array(
							'id'   => $stored_field->get_ID(),
							'name' => $stored_field->get_name(),
							'icon' => $icon,
							'type' => $stored_field->get_type(),
						);
					}

					$field_data = apply_filters( 'wpum_registration_form_get_stored_field', $field_data, $field, $form );

					if ( ! empty( $field_data ) ) {
						$fields[] = $field_data;
					}
				}
			}
		}

		return $fields;

	}

	/**
	 * Get fields available to be used within a registration form.
	 *
	 * @param mixed $form_id
	 *
	 * @return array
	 */
	private function get_available_fields( $form_id ) {

		$fields = array();

		$available_fields = WPUM()->fields->get_fields( array(
			'orderby' => 'fields_order',
			'order'   => 'ASC',
		) );

		$non_allowed_fields = array(
			'user_nickname',
			'user_displayname',
		);

		if ( ! wpum_get_option( 'custom_avatars' ) ) {
			$non_allowed_fields[] = 'user_avatar';
		}

		if ( wpum_get_option( 'disable_profile_cover' ) ) {
			$non_allowed_fields[] = 'user_cover';
		}

		$non_allowed_fields = apply_filters( 'wpum_non_allowed_fields', $non_allowed_fields );

		// Get fields already been used.
		$form          = new WPUM_Registration_Form( $form_id );
		$stored_fields = $form->get_fields();
		$stored_fields = empty( $stored_fields ) ? array() : $stored_fields;

		foreach ( $available_fields as $field ) {
			$primary_id = $field->get_primary_id();
			if ( ! empty( $primary_id ) && in_array( $primary_id, $non_allowed_fields, true ) ) {
				continue;
			}

			if ( in_array( (int) $field->get_ID(), array_map( 'intval', $stored_fields ), true ) ) {
				continue;
			}

			$icon = isset( $field->field_type->icon ) ? $field->field_type->icon : 'dashicons-editor-justify';

			$fields[] = array(
				'id'   => $field->get_ID(),
				'name' => $field->get_name(),
				'icon' => $icon,
				'type' => $field->get_type(),
			);
		}

		return apply_filters( 'wpum_registration_form_available_fields', $fields, $form_id );
	}

	/**
	 * Save fields for this form to the database.
	 *
	 * @return void
	 */
	public function save_form() {

		check_ajax_referer( 'wpum_save_registration_form', 'nonce' );

		if ( current_user_can( $this->capability ) && is_admin() ) {

			$form_id = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
			$fields  = filter_input( INPUT_POST, 'fields', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$fields  = $fields ? $fields : array();

			if ( $form_id ) {
				$registration_form = new WPUM_Registration_Form( $form_id );
				$fields_to_save    = array();

				if ( $registration_form->exists() ) {
					foreach ( $fields as $field ) {
						$fields_to_save[] = apply_filters( 'wpum_save_registration_form_field_id', absint( $field['id'] ), $field['id'], $field );
					}
				}

				if ( ! empty( $fields_to_save ) ) {
					$registration_form->update_meta( 'fields', $fields_to_save );

					$this->delete_registration_forms_cache();
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

		if ( ! current_user_can( $this->capability ) || ! is_admin() ) {
			$this->send_json_error();
		}

		$form_id        = filter_input( INPUT_POST, 'form_id', FILTER_VALIDATE_INT );
		$settings_model = filter_input( INPUT_POST, 'settings_model', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$settings_model = ! empty( $settings_model ) ? $settings_model : array();

		if ( empty( $form_id ) ) {
			$this->send_json_error();
		}

		$form = new WPUM_Registration_Form( $form_id );

		if ( ! $form->exists() ) {
			$this->send_json_error();
		}

		$registered_settings = $form->get_settings_options();
		$settings            = array();
		foreach ( $registered_settings as $registered_setting ) {
			$settings[ $registered_setting['id'] ] = $registered_setting;
		}
		$stored_settings_model = $form->get_settings_model();

		$deleted_settings = array_diff_key( $stored_settings_model, $settings_model );

		foreach ( $deleted_settings as $deleted_key => $deleted_setting ) {
			$form->delete_meta( $deleted_key, '' );
		}

		foreach ( $settings_model as $key => $value ) {
			if ( ! isset( $settings[ $key ] ) ) {
				continue;
			}

			$setting = $settings[ $key ];

			$value = apply_filters( 'wpum_form_settings_sanitize_' . $setting['type'], $value );

			if ( isset( $stored_settings_model[ $key ] ) && $value === $stored_settings_model[ $key ] ) {
				// Setting not changed
				continue;
			}

			if ( 'role' === $key && is_array( $value ) && ! empty( $value ) && ( 'administrator' === $value[0] || ! get_role( $value[0] ) ) ) {
				// Illegal role selection;
				continue;
			}

			$form->update_meta( $key, $value );

		}

		wp_send_json_success();
	}

	/**
	 * Get form data
	 */
	public function get_form_field_data() {

		check_ajax_referer( 'wpum_get_registration_form', 'nonce' );

		$parent_id = ! empty( $_GET['parent_id'] ) ? intval( $_GET['parent_id'] ) : 0;

		if ( ! $parent_id ) {
			$this->send_json_error();
		}

		$field = new WPUM_Field( $parent_id );

		if ( ! $field ) {
			$this->send_json_error();
		}

		$fields = WPUM()->fields->get_fields(
			array(
				'orderby'  => 'field_order',
				'order'    => 'ASC',
				'parent'   => $field->get_ID(),
				'group_id' => $field->get_group_id(),
			)
		);

		$response = array();

		foreach ( $fields as $field ) {
			$response[] = array(
				'id'   => $field->get_ID(),
				'name' => $field->get_name(),
				'type' => $field->get_type(),
				'icon' => $field->field_type->icon ? $field->field_type->icon : 'dashicons-editor-justify',
			);
		}

		wp_send_json_success( array( 'fields' => $response ) );
	}


	/**
	 * Sanitize the text field.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	public function sanitize_text_field( $input ) {
		return trim( wp_strip_all_tags( $input, true ) );
	}

	/**
	 * Sanitize textarea field.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	public function sanitize_textarea_field( $input ) {
		return stripslashes( wp_kses_post( $input ) );
	}

	/**
	 * Sanitize multiselect and multicheck field.
	 *
	 * @param mixed $input
	 *
	 * @return array
	 */
	public function sanitize_multiple_field( $input ) {
		$new_input = array();

		if ( is_array( $input ) && ! empty( $input ) ) {
			foreach ( $input as $key => $value ) {
				$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		if ( ! empty( $input ) && ! is_array( $input ) ) {
			$input = explode( ',', $input );
			foreach ( $input as $key => $value ) {
				$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
			}
		}

		return $new_input;
	}

	/**
	 * Sanitize urls for the file field.
	 *
	 * @param string $input
	 *
	 * @return string
	 */
	public function sanitize_file_field( $input ) {
		return esc_url( $input );
	}

	/**
	 * Sanitize the checkbox field.
	 *
	 * @param string $input
	 *
	 * @return bool
	 */
	public function sanitize_checkbox_field( $input ) {
		$pass = false;

		if ( 'true' === $input ) {
			return true;
		}

		if ( 1 === (int) $input ) {
			return true;
		}

		return $pass;
	}

	/**
	 * Send and error back to the ajax request.
	 *
	 * @return void
	 */
	private function send_json_error() {
		wp_send_json_error( null, 403 );
	}

	/**
	 * Delete forms cache
	 */
	public function delete_registration_forms_cache() {
		$cache_key = WPUM()->registration_forms->get_cache_key_from_args();

		wp_cache_delete( $cache_key, WPUM()->registration_forms->cache_group );
	}

	/**
	 * Get all available edit form sections
	 *
	 * @return array
	 */
	public function form_edit_sections() {
		$default = array(
			'settings' => __( 'Settings', 'wp-user-manager' ),
		);

		$subsections = apply_filters( 'wpum_registered_settings_sections', array() );
		$subsections = isset( $subsections['registration'] ) ? $subsections['registration'] : array();

		$section_options = apply_filters( 'wpum_registration_edit_form_sections', array() );

		return array_merge( $default, $subsections, $section_options );
	}
}

$wpum_registration_forms_editor = new WPUM_Registration_Forms_Editor();
