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

/**
 * WPUM_Roles_Editor
 */
class WPUM_Roles_Editor {

	/**
	 * @var string
	 */
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
		add_action( 'admin_menu', array( $this, 'setup_menu_page' ), 9 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_wpum_get_roles', array( $this, 'get_roles' ) );
		add_action( 'wp_ajax_wpum_get_role', array( $this, 'get_role' ) );
		add_action( 'wp_ajax_wpum_save_role', array( $this, 'save_role' ) );
		add_action( 'wp_ajax_wpum_update_role', array( $this, 'update_role' ) );
		add_action( 'wp_ajax_wpum_delete_role', array( $this, 'delete_role' ) );
		add_action( 'wp_ajax_wpum_create_role', array( $this, 'create_role' ) );
	}

	/**
	 * Add the registration forms editor link to the admin dashboard menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page( esc_html__( 'Roles', 'wp-user-manager' ), esc_html__( 'Roles', 'wp-user-manager' ), $this->capability, 'wpum-roles', array(
			$this,
			'display_roles_editor',
		) );
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

		if ( 'users_page_wpum-roles' === $screen->base ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV;

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

			$js_variables = array(
				'labels'            => $this->get_labels(),
				'ajax'              => admin_url( 'admin-ajax.php' ),
				'pluginURL'         => WPUM_PLUGIN_URL,
				'getRolesNonce'     => wp_create_nonce( 'wpum_get_roles' ),
				'getRoleNonce'      => wp_create_nonce( 'wpum_get_role' ),
				'saveRoleNonce'     => wp_create_nonce( 'wpum_save_role' ),
				'createRoleNonce'   => wp_create_nonce( 'wpum_create_role' ),
				'nonce'             => wp_create_nonce( 'wpum_update_role' ),
				'delete_role_nonce' => wp_create_nonce( 'wpum_delete_role' ),
				'usersURL'          => admin_url( 'users.php' ),
				'changeDefaultURL'  => admin_url( 'options-general.php#default_role' ),
			);

			wp_localize_script( 'wpum-roles-editor', 'wpumRolesEditor', $js_variables );
		}

	}

	/**
	 * Setup the labels for translation.
	 *
	 * @return array
	 */
	private function get_labels() {
		$labels = array(
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
			'table_edit_role'       => esc_html__( 'Edit Role', 'wp-user-manager' ),
			'table_signup_total'    => esc_html__( 'Total Signups', 'wp-user-manager' ),
			'table_shortcode'       => esc_html__( 'Shortcode', 'wp-user-manager' ),
			'table_actions'         => esc_html__( 'Actions', 'wp-user-manager' ),
			'table_not_found'       => esc_html__( 'No roles have been found.', 'wp-user-manager' ),
			'table_add_role'        => esc_html__( 'Add New Role', 'wp-user-manager' ),
			'table_add_cap'         => esc_html__( 'Add New Capability', 'wp-user-manager' ),
			'table_default_tooltip' => esc_html__( 'The default role cannot be deleted.', 'wp-user-manager' ),
			'table_duplicate_role'  => esc_html__( 'Duplicate', 'wp-user-manager' ),
			'table_delete_role'     => esc_html__( 'Delete', 'wp-user-manager' ),
			'table_customize'       => esc_html__( 'Edit Capabilities', 'wp-user-manager' ),
			'add_new_cap'           => esc_html__( 'Add Custom Capability', 'wp-user-manager' ),
			'page_back'             => esc_html__( 'Return to the roles list', 'wp-user-manager' ),
			'success'               => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
			'cap_success'           => esc_html__( 'Capabilities successfully saved.', 'wp-user-manager' ),
			'error'                 => esc_html__( 'Something went wrong no changes saved.', 'wp-user-manager' ),
			'save'                  => esc_html__( 'Save Changes', 'wp-user-manager' ),
			'tooltip_role_name'     => esc_html__( 'Customize the name of the role.', 'wp-user-manager' ),
			'tooltip_cap_name'      => esc_html__( 'Customize the name of the capability.', 'wp-user-manager' ),
			'create_role'           => esc_html__( 'Create Role', 'wp-user-manager' ),
			'create_cap'            => esc_html__( 'Create Capability', 'wp-user-manager' ),
			'success_message'       => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
		);

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
			$roles        = array();

			foreach ( $all_roles as $role ) {
				$data = array(
					'id'                    => $role->name,
					'slug'                  => $role->name,
					'name'                  => $role->label,
					'default'               => $default_role && $default_role === $role->name,
					'count'                 => wpum_get_role_user_count( $role->name ),
					'granted_count'         => wpum_get_role_granted_cap_count( $role->name ),
					'denied_count'          => wpum_get_role_denied_cap_count( $role->name ),
					'current_user_has_role' => current_user_can( $role->name ),
				);

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
			$role_id   = filter_input( INPUT_GET, 'role_id' );

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

				wp_send_json_success( array(
					'name'         => $role->label,
					'role'         => $role,
					'capabilities' => $capabilities,
					'groups'       => $groups,
					'is_editable'  => $is_editable,
				) );

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
			$role_id   = filter_input( INPUT_POST, 'role_id' );

			if ( $role_id && isset( $all_roles[ $role_id ] ) ) {

				$role        = wpum_get_role( $role_id );
				$curent_caps = array_keys( $role->caps );
				$wp_role     = get_role( sanitize_text_field( $role_id ) );

				$granted_caps           = filter_input( INPUT_POST, 'granted_caps', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				$denied_caps            = filter_input( INPUT_POST, 'denied_caps', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
				$submitted_granted_caps = $granted_caps ? $granted_caps : array();
				$submitted_denied_caps  = $denied_caps ? $denied_caps : array();

				$caps_to_remove = array_diff( $curent_caps, $submitted_granted_caps, $submitted_denied_caps );
				$custom_caps    = array();

				foreach ( $caps_to_remove as $cap ) {
					$cap = sanitize_text_field( $cap );
					$wp_role->remove_cap( $cap );
				}

				foreach ( $submitted_granted_caps as $cap ) {
					$cap = sanitize_text_field( $cap );
					if ( ! in_array( $cap, $curent_caps, true ) ) {
						$custom_caps[] = $cap;
					}

					$wp_role->add_cap( $cap );
				}

				foreach ( $submitted_denied_caps as $cap ) {
					$cap = sanitize_text_field( $cap );
					if ( ! in_array( $cap, $curent_caps, true ) ) {
						$custom_caps[] = $cap;
					}
					$wp_role->add_cap( $cap, false );
				}

				if ( ! empty( $custom_caps ) ) {
					$all_group    = wpum_get_cap_group( 'all' );
					$custom_group = wpum_get_cap_group( 'custom' );

					foreach ( $custom_caps as $custom_cap ) {

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
	 * Update a form via ajax.
	 *
	 * @return void
	 */
	public function update_role() {
		check_ajax_referer( 'wpum_update_role', 'nonce' );

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the role details.', 'wp-user-manager' ), 403 );
		}

		$role_id   = filter_input( INPUT_POST, 'role_id', FILTER_SANITIZE_STRING );
		$role_name = filter_input( INPUT_POST, 'role_name', FILTER_SANITIZE_STRING );

		if ( $role_id && $role_name ) {

			$data = apply_filters( 'wpum_role_update', array(
				'name' => sanitize_text_field( $role_name ),
			), sanitize_text_field( $role_id ) );

		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the registration form details.', 'wp-user-manager' ), 403 );
		}

		wp_send_json_success( array(
			'id'   => $role_id,
			'name' => $role_name,
		) );
	}

	/**
	 * Delete role
	 */
	public function delete_role() {
		check_ajax_referer( 'wpum_delete_role', 'nonce' );

		$role_id = filter_input( INPUT_POST, 'role_id', FILTER_SANITIZE_STRING );
		$role_id = sanitize_text_field( $role_id );

		if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'delete_roles' ) || empty( $role_id ) ) {
			wp_die( esc_html__( 'Something went wrong: could not delete the role.', 'wp-user-manager' ), 403 );
		}

		// Get the default role.
		$default_role = get_option( 'default_role' );

		// Don't delete the default role. Site admins should change the default before attempting to delete the role.
		if ( $role_id === $default_role ) {
			wp_die( esc_html__( 'Something went wrong: cannot delete the default role.', 'wp-user-manager' ), 403 );
		}

		// Get all users with the role to be deleted.
		$users = get_users( array( 'role' => $role_id ) );

		foreach ( $users as $user ) {
			if ( $user->has_cap( $role_id ) && 1 >= count( $user->roles ) ) {
				$user->set_role( $default_role );
			} elseif ( $user->has_cap( $role_id ) ) {
				$user->remove_role( $role_id );
			}
		}

		remove_role( $role_id );
		wpum_unregister_role( $role_id );

		do_action( 'wpum_role_delete', $role_id );

		wp_send_json_success( (string) $role_id );

	}

	/**
	 * Store new forms into the database.
	 *
	 * @return void
	 */
	public function create_role() {
		check_ajax_referer( 'wpum_create_role', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Something went wrong: could not create new role.', 'wp-user-manager' ), 403 );
		}

		$role_name = filter_input( INPUT_POST, 'role_name', FILTER_SANITIZE_STRING );
		$role_name = sanitize_text_field( $role_name );

		if ( $role_name ) {
			$role_id = strtolower( sanitize_file_name( $role_name ) );

			$new_role = add_role( $role_id, $role_name );
			$args     = array(
				'label' => $role_name,
			);

			$orig_role_id = filter_input( INPUT_POST, 'orig_role_id', FILTER_SANITIZE_STRING );

			if ( $orig_role_id ) {
				$orig_role = wpum_get_role( $orig_role_id );

				foreach ( $orig_role->caps as $orig_cap => $value ) {
					$args['caps'][ $orig_cap ] = $value;
				}

				foreach ( $orig_role->granted_caps as $cap ) {
					$args['granted_caps'][] = $cap;
					$new_role->add_cap( $cap );
				}

				foreach ( $orig_role->denied_caps as $cap ) {
					$args['denied_caps'][] = $cap;
					$new_role->add_cap( $cap, false );
				}
			}

			wpum_register_role( $role_id, $args );

			do_action( 'wpum_role_add', $role_id );

			$default_role = get_option( 'default_role' );

			$role = wpum_get_role( $role_id );

			$data = array(
				'id'                    => $role->name,
				'slug'                  => $role->name,
				'name'                  => $role->label,
				'default'               => $default_role && $default_role === $role->name,
				'count'                 => wpum_get_role_user_count( $role->name ),
				'granted_count'         => wpum_get_role_granted_cap_count( $role->name ),
				'denied_count'          => wpum_get_role_denied_cap_count( $role->name ),
				'current_user_has_role' => current_user_can( $role->name ),
			);

			wp_send_json_success( $data );

		} else {
			wp_die( esc_html__( 'Something went wrong: could not create new role.', 'wp-user-manager' ), 403 );
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

$wpum_roles_editor = new WPUM_Roles_Editor();
