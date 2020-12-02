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
		if ( ! wpum_get_option( 'roles_editor' ) ) {
			return;
		}
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_ajax_wpum_get_roles', [ $this, 'get_roles' ] );
		add_action( 'wp_ajax_wpum_get_role', [ $this, 'get_role' ] );
		add_action( 'wp_ajax_wpum_save_role', [ $this, 'save_role' ] );
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
				'usersURL'          => admin_url( 'users.php' ),
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
			'table_cap_name'        => esc_html__( 'Capability Name', 'wp-user-manager' ),
			'table_slug'            => esc_html__( 'Role', 'wp-user-manager' ),
			'table_users'           => esc_html__( 'Users', 'wp-user-manager' ),
			'table_granted'         => esc_html__( 'Granted', 'wp-user-manager' ),
			'table_denied'          => esc_html__( 'Denied', 'wp-user-manager' ),
			'table_default'         => esc_html__( 'Default', 'wp-user-manager' ),
			'table_edit'            => esc_html__( 'Edit', 'wp-user-manager' ),
			'table_signup_total'    => esc_html__( 'Total Signups', 'wp-user-manager' ),
			'table_shortcode'       => esc_html__( 'Shortcode', 'wp-user-manager' ),
			'table_actions'         => esc_html__( 'Actions', 'wp-user-manager' ),
			'table_not_found'       => esc_html__( 'No roles have been found.', 'wp-user-manager' ),
			'table_add_role'        => esc_html__( 'Add New Role', 'wp-user-manager' ),
			'table_add_cap'         => esc_html__( 'Add New Capability', 'wp-user-manager' ),
			'table_default_tooltip' => esc_html__( 'The default role cannot be deleted.', 'wp-user-manager' ),
			'table_delete_role'     => esc_html__( 'Delete Role', 'wp-user-manager' ),
			'table_customize'       => esc_html__( 'Edit Capabilities', 'wp-user-manager' ),
			'add_new_cap'           => esc_html__( 'Add Custom Capability', 'wp-user-manager' ),
			'page_back'             => esc_html__( 'Return to the roles list', 'wp-user-manager' ),
			'success'               => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
			'cap_success'           => esc_html__( 'Capabilities successfully saved.', 'wp-user-manager' ),
			'error'                 => esc_html__( 'Something went wrong no changes saved.', 'wp-user-manager' ),
			'save'                  => esc_html__( 'Save Changes', 'wp-user-manager' ),
			'tooltip_form_name'     => esc_html__( 'Customize the name of the role.', 'wp-user-manager' ),
			'tooltip_cap_name'      => esc_html__( 'Customize the name of the capability.', 'wp-user-manager' ),
			'create_role'           => esc_html__( 'Create Role', 'wp-user-manager' ),
			'create_cap'            => esc_html__( 'Create Capability', 'wp-user-manager' ),
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
			$all_roles    = wpum_get_all_roles();
			$roles        = [];

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

				// Get all the capabilities.
				$capabilities = wpum_get_capabilities();

				$groups = wpum_get_cap_groups();

				// Add all caps from the cap groups.
				foreach ( $groups as $group ) {
					$capabilities = array_merge( $capabilities, $group->caps );
				}

				// Make sure we have a unique array of caps.
				$capabilities = array_unique( $capabilities );

				// Is the role editable?
				$is_editable = wpum_is_role_editable( $role_id );

				wp_send_json_success( [
					'name'         => $role->label,
					'role'         => $role,
					'capabilities' => $capabilities,
					'groups'       => $groups,
					'is_editable'  => $is_editable,
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
			$all_roles = wpum_get_all_roles();
			$role_id   = isset( $_POST['role_id'] ) ? $_POST['role_id'] : false;

			if ( $role_id && isset( $all_roles[ $role_id ] ) ) {

				$role = wpum_get_role( $role_id );

				$wp_role = get_role( sanitize_text_field( $role_id ) );

				$submitted_granted_caps = isset( $_POST['granted_caps'] ) ? $_POST['granted_caps'] : array();
				$submitted_denied_caps  = isset( $_POST['denied_caps'] ) ? $_POST['denied_caps'] : array();

				$caps_to_add    = array_diff( $submitted_granted_caps, $role->granted_caps );
				$caps_to_remove = array_diff( $role->granted_caps, $submitted_granted_caps );

				$caps_to_deny   = array_diff( $submitted_denied_caps, $role->denied_caps );
				$caps_to_undeny = array_diff( $role->denied_caps, $submitted_denied_caps );

				$custom_caps = array();

				foreach ( $caps_to_remove as $cap ) {
					$cap = sanitize_text_field( $cap );
					$wp_role->remove_cap( $cap );
				}

				foreach ( $caps_to_undeny as $cap ) {
					$cap = sanitize_text_field( $cap );
					$wp_role->remove_cap( $cap );
				}

				foreach ( $caps_to_add as $cap ) {
					$cap = sanitize_text_field( $cap );
					if ( ! in_array( $cap, $role->caps ) ) {
						$custom_caps[] = $cap;
					}

					$wp_role->add_cap( $cap );
				}

				foreach ( $caps_to_deny as $cap ) {
					$cap = sanitize_text_field( $cap );
					if ( ! in_array( $cap, $role->caps ) ) {
						$custom_caps[] = $cap;
					}
					$wp_role->add_cap( $cap, false );
				}

				if ( ! empty( $custom_caps ) ) {
					$all_group    = wpum_get_cap_group( 'all' );
					$custom_group = wpum_get_cap_group( 'custom' );

					foreach( $custom_caps as $custom_cap ) {

							if ( $all_group ) {
								$all_group->caps[] = $custom_cap;
								sort( $all_group->caps );
							}

							if ( $custom_group ) {
								$custom_group->caps[] = $custom_cap;
								sort( $custom_group->caps );
							}
					}
				}

				wp_send_json_success();
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
	 * Send and error back to the ajax request.
	 *
	 * @return void
	 */
	private function send_json_error() {
		wp_send_json_error( null, 403 );
	}

}

$wpum_roles_editor = new WPUM_Roles_Editor;
