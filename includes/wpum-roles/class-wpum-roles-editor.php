<?php
/**
 * Handles the roles editor.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPUM_Roles_Editor {

	protected $capability;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init_hooks();
		$this->capability = apply_filters( 'wpum_admin_pages_capability', 'manage_options' );
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_ajax_wpum_get_roles', [ $this, 'get_roles' ] );
		add_action( 'wp_ajax_wpum_get_role', [ $this, 'get_role' ] );
		add_action( 'wp_ajax_wpum_update_role', [ $this, 'update_role' ] );
	}

	/**
	 * Add the registration forms editor link to the admin dashboard menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page( esc_html__( 'Roles', 'wp-user-manager' ), esc_html__( 'Roles', 'wp-user-manager' ), $this->capability, 'wpum-roles', [
				$this,
				'display_roles_editor',
			] );
	}

	/**
	 * Registration forms editor page. Display is handled by Vuejs.
	 *
	 * @return void
	 */
	public function display_roles_editor() {
		echo '<div class="wrap"><div id="wpum-roles-editor"></div></div>';
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if ( $screen->base == 'users_page_wpum-roles' ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if ( $is_vue_dev ) {
				$vue_dev_port = defined( 'WPUM_VUE_DEV_PORT' ) ? WPUM_VUE_DEV_PORT : '8080';
				wp_register_script( 'wpum-roles-editor', 'http://localhost:' . $vue_dev_port . '/roles-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_register_script( 'wpum-roles-editor', WPUM_PLUGIN_URL . 'dist/static/js/roles-editor.js', array(), WPUM_VERSION, true );
			}

			if ( ! $is_vue_dev ) {
				wp_enqueue_script( 'wpum-vue-manifest' );
				wp_enqueue_script( 'wpum-vue-vendor' );
				wp_enqueue_style( 'wpum-roles-editor-css', WPUM_PLUGIN_URL . 'dist/static/css/roles-editor.css', array(), WPUM_VERSION );
			}

			wp_enqueue_script( 'wpum-roles-editor' );
			wp_enqueue_style( 'wpum-registration-forms-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css', array(), WPUM_VERSION );
			wp_enqueue_style( 'wpum-roles-editor-ok', WPUM_PLUGIN_URL . 'vendor/wp-user-manager/wp-optionskit/dist/static/css/app.css', array(), WPUM_VERSION );

			$js_variables = [
				'labels'            => $this->get_labels(),
				'ajax'              => admin_url( 'admin-ajax.php' ),
				'pluginURL'         => WPUM_PLUGIN_URL,
				'getRolesNonce'     => wp_create_nonce( 'wpum_get_roles' ),
				'getRoleNonce'      => wp_create_nonce( 'wpum_get_role' ),
				'saveRoleNonce'     => wp_create_nonce( 'wpum_save_role' ),
				'nonce'             => wp_create_nonce( 'wpum_update_role' ),
				'delete_role_nonce' => wp_create_nonce( 'wpum_delete_role' ),
			];

			wp_localize_script( 'wpum-roles-editor', 'wpumRolesEditor', $js_variables );
		}

	}

	/**
	 * Setup the labels for translation.
	 *
	 * @return array
	 */
	private function get_labels() {
		$labels = [
			'confirm_delete'        => esc_html__( 'Confirm Role Deletion?', 'wp-user-manager' ),
			'modal_form_delete'     => esc_html__( 'You are about to delete the role:', 'wp-user-manager' ),
			'modal_delete'          => esc_html__( 'This action cannot be reversed. Are you sure you want to continue?', 'wp-user-manager' ),
			'page_title'            => esc_html__( 'Roles', 'wp-user-manager' ),
			'table_name'            => esc_html__( 'Role Name', 'wp-user-manager' ),
			'table_slug'            => esc_html__( 'Role', 'wp-user-manager' ),
			'table_users'           => esc_html__( 'Users', 'wp-user-manager' ),
			'table_granted'         => esc_html__( 'Granted', 'wp-user-manager' ),
			'table_denied'          => esc_html__( 'Denied', 'wp-user-manager' ),
			'table_default'         => esc_html__( 'Default', 'wp-user-manager' ),
			'table_edit'       => esc_html__( 'Edit', 'wp-user-manager' ),
			'table_signup_total'    => esc_html__( 'Total Signups', 'wp-user-manager' ),
			'table_shortcode'       => esc_html__( 'Shortcode', 'wp-user-manager' ),
			'table_actions'         => esc_html__( 'Actions', 'wp-user-manager' ),
			'table_not_found'       => esc_html__( 'No roles have been found.', 'wp-user-manager' ),
			'table_add_role'        => esc_html__( 'Add New Role', 'wp-user-manager' ),
			'table_default_tooltip' => esc_html__( 'The default role cannot be deleted.', 'wp-user-manager' ),
			'table_delete_role'     => esc_html__( 'Delete Role', 'wp-user-manager' ),
			'table_customize'       => esc_html__( 'Edit Role', 'wp-user-manager' ),
			'add_new_cap'       => esc_html__( 'Add Custom Capability', 'wp-user-manager' ),
			'page_back'             => esc_html__( 'Return to the roles list', 'wp-user-manager' ),
			'success'               => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
			'error'                 => esc_html__( 'Something went wrong no changes saved.', 'wp-user-manager' ),
			'save'                  => esc_html__( 'Save Changes', 'wp-user-manager' ),
			'tooltip_form_name'     => esc_html__( 'Customize the name of the role.', 'wp-user-manager' ),
			'create_role'           => esc_html__( 'Create Role', 'wp-user-manager' ),
			'success_message'       => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
		];

		return $labels;
	}

	/**
	 * Retrieve the list of registration forms.
	 *
	 * @return void
	 */
	public function get_roles() {
		check_ajax_referer( 'wpum_get_roles', 'nonce' );

		if ( current_user_can( $this->capability ) && is_admin() ) {

			$default_role = get_option( 'default_role' );
			$all_roles = wpum_get_all_roles();
			$roles     = [];

			foreach ( $all_roles as $role ) {
				$data = [
					'id'            => $role->name,
					'slug'          => $role->name,
					'name'          => $role->label,
					'default'       => $default_role && $default_role == $role->name,
					'count'         => wpum_get_role_user_count( $role->name ),
					'granted_count' => wpum_get_role_granted_cap_count( $role->name ),
					'denied_count'  => wpum_get_role_denied_cap_count( $role->name ),
				];

				$roles[] = apply_filters( 'wpum_get_role_data_for_table', $data );
			}

			if ( is_array( $roles ) && ! empty( $roles ) ) {
				wp_send_json_success( $roles );
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

		$form_id   = isset( $_POST['form_id'] ) && ! empty( $_POST['form_id'] ) ? (int) $_POST['form_id'] : false;
		$form_name = isset( $_POST['form_name'] ) && ! empty( $_POST['form_name'] ) ? sanitize_text_field( $_POST['form_name'] ) : false;

		if ( $form_id && $form_name ) {

			$data = apply_filters( 'wpum_registration_form_update', [
				'name' => $form_name,
			], $form_id );

			$updated_form = WPUM()->registration_forms->update( $form_id, $data );

		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the registration form details.', 'wp-user-manager' ), 403 );
		}

		wp_send_json_success( [
			'id'   => $form_id,
			'name' => $form_name,
		] );
	}

	/**
	 * Retrieve a single role
	 *
	 * @return void
	 */
	public function get_role() {
		check_ajax_referer( 'wpum_get_role', 'nonce' );

		if ( current_user_can( $this->capability ) && is_admin() ) {
			$all_roles = wpum_get_all_roles();
			$role_id   = isset( $_GET['role_id'] ) ? $_GET['role_id'] : false;

			if ( $role_id && isset( $all_roles[ $role_id ] ) ) {

				$role = wpum_get_role( $role_id );

				wp_send_json_success( [
					'name'             => $role->label,
					'slug'             => $role->name
				] );

			} else {
				$this->send_json_error();
			}

		} else {
			$this->send_json_error();
		}
	}

	/**
	 * Save fields for this form to the database.
	 *
	 * @return void
	 */
	public function save_role() {

		check_ajax_referer( 'wpum_save_role', 'nonce' );

		if ( current_user_can( $this->capability ) && is_admin() ) {

			$role_id = isset( $_POST['role_id'] ) ? absint( $_POST['role_id'] ) : false;

			if ( $role_id ) {
//				$registration_form = new WPUM_Registration_Form( $role_id );
//				$fields_to_save    = [];
//
//				if ( $registration_form->exists() ) {
//					foreach ( $fields as $field ) {
//						$fields_to_save[] = absint( $field['id'] );
//					}
//				}

				if ( ! empty( $fields_to_save ) ) {
					//$registration_form->update_meta( 'fields', $fields_to_save );
					wp_send_json_success();
				}

			}

		} else {

			$this->send_json_error();

		}

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

		if ( $input == 'true' ) {
			return true;
		}

		if ( 1 == $input ) {
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

}

$wpum_roles_editor = new WPUM_Roles_Editor;
