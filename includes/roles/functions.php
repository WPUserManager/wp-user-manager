<?php
/**
 * Handles functions for roles.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

add_action( 'wp_roles_init', 'wpum_register_roles', 100 );
add_action( 'wpum_register_roles', 'wpum_register_default_roles', 5 );
add_action( 'init', 'wpum_register_caps', 100 );
add_action( 'wpum_register_caps', 'wpum_register_default_caps', 5 );
add_filter( 'wpum_get_capabilities', 'wpum_remove_old_levels' );
add_filter( 'wpum_get_capabilities', 'wpum_remove_hidden_caps' );

add_action( 'init', 'wpum_register_cap_groups', 100 );
add_action( 'wpum_register_cap_groups', 'wpum_register_default_cap_groups', 5 );


/**
 * @param WP_Roles $wp_roles
 */
function wpum_register_roles( WP_Roles $wp_roles ) {
	do_action( 'wpum_register_roles', $wp_roles );
}

/**
 * Registers WordPress roles.
 *
 * @param WP_Roles $wp_roles
 */
function wpum_register_default_roles( WP_Roles $wp_roles ) {
	foreach ( $wp_roles->roles as $name => $object ) {
		$args = array(
			'label' => $object['name'],
			'caps'  => $object['capabilities'],
		);

		wpum_register_role( $name, $args );
	}

	foreach ( wpum_get_all_roles() as $role ) {
		if ( ! isset( $wp_roles->roles[ $role->name ] ) ) {
			wpum_unregister_role( $role->name );
		}
	}
}

/**
 * Returns the instance of the roles collection.
 *
 * @return WPUM_Collection|WPUM_Roles
 */
function wpum_role_collection() {
	return WPUM_Roles::get_instance( 'role' );
}

/**
 * Returns all registered roles.
 *
 * @return array
 * @since  1.0.0
 * @access public
 */
function wpum_get_all_roles() {
	return wpum_role_collection()->get_items();
}

/**
 * Registers a role.
 *
 * @param string $name
 * @param array  $args
 */
function wpum_register_role( $name, $args = array() ) {
	wpum_role_collection()->register( $name, new WPUM_Role( $name, $args ) );
}

/**
 * Unregisters a role
 *
 * @param string $name
 */
function wpum_unregister_role( $name ) {
	wpum_role_collection()->unregister( $name );
}

/**
 * Returns a role object.
 *
 * @param string $name
 *
 * @return WPUM_Role
 */
function wpum_get_role( $name ) {
	return wpum_role_collection()->get( $name );
}

/**
 * Checks if a role object exists.
 *
 * @param string $name
 *
 * @return bool
 */
function wpum_role_exists( $name ) {
	return wpum_role_collection()->exists( $name );
}

/**
 * Returns an array of editable roles.
 *
 * @return array
 * @global array $wp_roles
 */
function wpum_get_editable_roles() {
	global $wp_roles;

	$editable = function_exists( 'get_editable_roles' ) ? get_editable_roles() : apply_filters( 'editable_roles', $wp_roles->roles );

	return array_keys( $editable );
}

/**
 * Returns an array of uneditable roles.
 *
 * @return array
 */
function wpum_get_uneditable_roles() {
	return array_diff( array_keys( wpum_get_all_roles() ), wpum_get_editable_roles() );
}

/**
 * Returns an array of core WP roles.  Note that we remove any that are not registered.
 *
 * @return array
 */
function wpum_get_wordpress_roles() {
	$roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );

	return array_intersect( $roles, array_keys( wpum_get_all_roles() ) );
}

/**
 * Returns an array of the roles that have users.
 *
 * @return array
 */
function wpum_get_active_roles() {
	$has_users = array();

	foreach ( wpum_get_role_user_count() as $role => $count ) {
		if ( 0 < $count ) {
			$has_users[] = $role;
		}
	}

	return $has_users;
}

/**
 * Returns an array of the roles that have no users.
 *
 * @return array
 * @since  2.0.0
 * @access public
 */
function wpum_get_inactive_roles() {
	return array_diff( array_keys( wpum_get_all_roles() ), wpum_get_active_roles() );
}

/**
 * Returns a count of all the available roles for the site.
 *
 * @return int
 * @since  1.0.0
 * @access public
 */
function wpum_get_role_count() {
	return count( $GLOBALS['wp_roles']->role_names );
}

/**
 * @param string $role
 *
 * @return string
 */
function wpum_sanitize_role( $role ) {
	$_role = strtolower( $role );
	$_role = preg_replace( '/[^a-z0-9_\-\s]/', '', $_role );

	return apply_filters( 'wpum_sanitize_role', str_replace( ' ', '_', $_role ), $role );
}

/**
 * @param string $role
 *
 * @return string
 */
function wpum_translate_role( $role ) {
	global $wp_roles;

	return wpum_translate_role_hook( $wp_roles->role_names[ $role ], $role );
}

/**
 * @param string $label
 * @param string $role
 *
 * @return string
 */
function wpum_translate_role_hook( $label, $role ) {
	return apply_filters( 'wpum_translate_role', translate_user_role( $label ), $role );
}

/**
 * @param string $role
 *
 * @return bool
 */
function wpum_role_has_users( $role ) {
	return in_array( $role, wpum_get_active_roles(), true );
}

/**
 * Conditional tag to check if a role has any capabilities.
 *
 * @param string $role
 *
 * @return bool
 */
function wpum_role_has_caps( $role ) {
	return wpum_get_role( $role )->has_caps;
}

/**
 * @param string $role
 *
 * @return int|array
 */
function wpum_get_role_user_count( $role = '' ) {
	if ( empty( wpum_role_collection()->counts ) ) {
		$user_count = count_users();
		foreach ( $user_count['avail_roles'] as $_role => $count ) {
			wpum_role_collection()->counts[ $_role ] = $count;
		}
	}

	if ( $role ) {
		return isset( wpum_role_collection()->counts[ $role ] ) ? wpum_role_collection()->counts[ $role ] : 0;
	}

	return wpum_role_collection()->counts;
}

/**
 * Returns the number of granted capabilities that a role has.
 *
 * @param string $role
 *
 * @return int
 */
function wpum_get_role_granted_cap_count( $role ) {
	return wpum_get_role( $role )->granted_cap_count;
}

/**
 * Returns the number of denied capabilities that a role has.
 *
 * @param string $role
 *
 * @return int
 */
function wpum_get_role_denied_cap_count( $role ) {
	return wpum_get_role( $role )->denied_cap_count;
}

/**
 * Conditional tag to check whether a role can be edited.
 *
 * @param string $role
 *
 * @return bool
 */
function wpum_is_role_editable( $role ) {
	return in_array( $role, wpum_get_editable_roles(), true );
}

/**
 * Conditional tag to check whether a role is a core WordPress role.
 *
 * @param string $role
 *
 * @return bool
 */
function wpum_is_wordpress_role( $role ) {
	return in_array( $role, array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' ), true );
}

/**
 * Fires the action hook for registering capabilities.
 */
function wpum_register_caps() {
	do_action( 'wpum_register_caps' );

	$role_caps    = array_values( wpum_get_role_capabilities() );
	$unregistered = array_diff( $role_caps, array_keys( wpum_get_caps() ) );

	foreach ( $unregistered as $cap ) {
		wpum_register_cap( $cap, array( 'label' => $cap ) );
	}

}

/**
 * Registers all of our default caps.
 */
function wpum_register_default_caps() {
	$caps = array();

	// General caps.
	$caps['edit_dashboard']    = array(
		'label' => __( 'Edit Dashboard', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['edit_files']        = array(
		'label' => __( 'Edit Files', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['export']            = array(
		'label' => __( 'Export', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['import']            = array(
		'label' => __( 'Import', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['manage_links']      = array(
		'label' => __( 'Manage Links', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['manage_options']    = array(
		'label' => __( 'Manage Options', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['moderate_comments'] = array(
		'label' => __( 'Moderate Comments', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['read']              = array(
		'label' => __( 'Read', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['unfiltered_html']   = array(
		'label' => __( 'Unfiltered HTML', 'wp-user-manager' ),
		'group' => 'general',
	);
	$caps['update_core']       = array(
		'label' => __( 'Update Core', 'wp-user-manager' ),
		'group' => 'general',
	);

	// Post caps.
	$caps['delete_others_posts']    = array(
		'label' => __( "Delete Others' Posts", 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['delete_posts']           = array(
		'label' => __( 'Delete Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['delete_private_posts']   = array(
		'label' => __( 'Delete Private Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['delete_published_posts'] = array(
		'label' => __( 'Delete Published Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['edit_others_posts']      = array(
		'label' => __( "Edit Others' Posts", 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['edit_posts']             = array(
		'label' => __( 'Edit Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['edit_private_posts']     = array(
		'label' => __( 'Edit Private Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['edit_published_posts']   = array(
		'label' => __( 'Edit Published Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['publish_posts']          = array(
		'label' => __( 'Publish Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);
	$caps['read_private_posts']     = array(
		'label' => __( 'Read Private Posts', 'wp-user-manager' ),
		'group' => 'type-post',
	);

	// Page caps.
	$caps['delete_others_pages']    = array(
		'label' => __( "Delete Others' Pages", 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['delete_pages']           = array(
		'label' => __( 'Delete Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['delete_private_pages']   = array(
		'label' => __( 'Delete Private Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['delete_published_pages'] = array(
		'label' => __( 'Delete Published Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['edit_others_pages']      = array(
		'label' => __( "Edit Others' Pages", 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['edit_pages']             = array(
		'label' => __( 'Edit Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['edit_private_pages']     = array(
		'label' => __( 'Edit Private Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['edit_published_pages']   = array(
		'label' => __( 'Edit Published Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['publish_pages']          = array(
		'label' => __( 'Publish Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);
	$caps['read_private_pages']     = array(
		'label' => __( 'Read Private Pages', 'wp-user-manager' ),
		'group' => 'type-page',
	);

	// Attachment caps.
	$caps['upload_files'] = array(
		'label' => __( 'Upload Files', 'wp-user-manager' ),
		'group' => 'type-attachment',
	);

	// Taxonomy caps.
	$caps['manage_categories'] = array(
		'label' => __( 'Manage Categories', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);

	// Theme caps.
	$caps['delete_themes']      = array(
		'label' => __( 'Delete Themes', 'wp-user-manager' ),
		'group' => 'theme',
	);
	$caps['edit_theme_options'] = array(
		'label' => __( 'Edit Theme Options', 'wp-user-manager' ),
		'group' => 'theme',
	);
	$caps['edit_themes']        = array(
		'label' => __( 'Edit Themes', 'wp-user-manager' ),
		'group' => 'theme',
	);
	$caps['install_themes']     = array(
		'label' => __( 'Install Themes', 'wp-user-manager' ),
		'group' => 'theme',
	);
	$caps['switch_themes']      = array(
		'label' => __( 'Switch Themes', 'wp-user-manager' ),
		'group' => 'theme',
	);
	$caps['update_themes']      = array(
		'label' => __( 'Update Themes', 'wp-user-manager' ),
		'group' => 'theme',
	);

	// Plugin caps.
	$caps['activate_plugins'] = array(
		'label' => __( 'Activate Plugins', 'wp-user-manager' ),
		'group' => 'plugin',
	);
	$caps['delete_plugins']   = array(
		'label' => __( 'Delete Plugins', 'wp-user-manager' ),
		'group' => 'plugin',
	);
	$caps['edit_plugins']     = array(
		'label' => __( 'Edit Plugins', 'wp-user-manager' ),
		'group' => 'plugin',
	);
	$caps['install_plugins']  = array(
		'label' => __( 'Install Plugins', 'wp-user-manager' ),
		'group' => 'plugin',
	);
	$caps['update_plugins']   = array(
		'label' => __( 'Update Plugins', 'wp-user-manager' ),
		'group' => 'plugin',
	);

	// User caps.
	$caps['create_roles']  = array(
		'label' => __( 'Create Roles', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['create_users']  = array(
		'label' => __( 'Create Users', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['delete_roles']  = array(
		'label' => __( 'Delete Roles', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['delete_users']  = array(
		'label' => __( 'Delete Users', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['edit_roles']    = array(
		'label' => __( 'Edit Roles', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['edit_users']    = array(
		'label' => __( 'Edit Users', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['list_roles']    = array(
		'label' => __( 'List Roles', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['list_users']    = array(
		'label' => __( 'List Users', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['promote_users'] = array(
		'label' => __( 'Promote Users', 'wp-user-manager' ),
		'group' => 'user',
	);
	$caps['remove_users']  = array(
		'label' => __( 'Remove Users', 'wp-user-manager' ),
		'group' => 'user',
	);

	// Custom caps.
	$caps['restrict_content'] = array(
		'label' => __( 'Restrict Content', 'wp-user-manager' ),
		'group' => 'custom',
	);

	// Register each of the capabilities.
	foreach ( $caps as $name => $args ) {
		wpum_register_cap( $name, $args );
	}

	$role_caps = array_values( wpum_get_role_capabilities() );
	$tax_caps  = array();

	$tax_caps['assign_categories'] = array(
		'label' => __( 'Assign Categories', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);
	$tax_caps['edit_categories']   = array(
		'label' => __( 'Edit Categories', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);
	$tax_caps['delete_categories'] = array(
		'label' => __( 'Delete Categories', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);
	$tax_caps['assign_post_tags']  = array(
		'label' => __( 'Assign Post Tags', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);
	$tax_caps['edit_post_tags']    = array(
		'label' => __( 'Edit Post Tags', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);
	$tax_caps['delete_post_tags']  = array(
		'label' => __( 'Delete Post Tags', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);
	$tax_caps['manage_post_tags']  = array(
		'label' => __( 'Manage Post Tags', 'wp-user-manager' ),
		'group' => 'taxonomy',
	);

	foreach ( $tax_caps as $tax_cap => $args ) {

		if ( in_array( $tax_cap, $role_caps, true ) ) {
			wpum_register_cap( $tax_cap, $args );
		}
	}
}

/**
 * Returns the instance of the capability registry.
 *
 * @return WPUM_Collection
 */
function wpum_capability_collection() {
	return WPUM_Collection::get_instance( 'cap' );
}

/**
 * Returns all registered caps.
 *
 * @return array
 */
function wpum_get_caps() {
	return wpum_capability_collection()->get_items();
}

/**
 * Registers a capability.
 *
 * @param string $name
 * @param array  $args
 */
function wpum_register_cap( $name, $args = array() ) {
	wpum_capability_collection()->register( $name, new WPUM_Capability( $name, $args ) );
}

/**
 * Unregisters a capability.
 *
 * @param string $name
 */
function wpum_unregister_cap( $name ) {
	wpum_capability_collection()->unregister( $name );
}

/**
 * Returns a capability object.
 *
 * @param string $name
 *
 * @return WPUM_Capability
 */
function wpum_get_cap( $name ) {
	return wpum_capability_collection()->get( $name );
}

/**
 * Checks if a capability object exists.
 *
 * @param string $name
 *
 * @return bool
 */
function wpum_cap_exists( $name ) {
	return wpum_capability_collection()->exists( $name );
}

/**
 * Function for sanitizing a capability.
 *
 * @param string $cap
 *
 * @return string
 */
function wpum_sanitize_cap( $cap ) {
	return apply_filters( 'wpum_sanitize_cap', sanitize_key( $cap ) );
}

/**
 * Checks if a capability is editable.  A capability is editable if it's not one of the core WP
 * capabilities and doesn't belong to an uneditable role.
 *
 * @param string $cap
 *
 * @return bool
 */
function wpum_is_cap_editable( $cap ) {
	$uneditable = array_keys( wpum_get_uneditable_roles() );

	return ! in_array( $cap, wpum_get_wp_capabilities(), true ) && ! array_intersect( $uneditable, wpum_get_cap_roles( $cap ) );
}

/**
 * @return array
 */
function wpum_get_wp_capabilities() {

	return array(
		'activate_plugins',
		'add_users',
		'assign_categories',
		'assign_post_tags',
		'create_users',
		'delete_categories',
		'delete_others_pages',
		'delete_others_posts',
		'delete_pages',
		'delete_plugins',
		'delete_posts',
		'delete_post_tags',
		'delete_private_pages',
		'delete_private_posts',
		'delete_published_pages',
		'delete_published_posts',
		'delete_themes',
		'delete_users',
		'edit_categories',
		'edit_dashboard',
		'edit_files',
		'edit_others_pages',
		'edit_others_posts',
		'edit_pages',
		'edit_plugins',
		'edit_posts',
		'edit_post_tags',
		'edit_private_pages',
		'edit_private_posts',
		'edit_published_pages',
		'edit_published_posts',
		'edit_theme_options',
		'edit_themes',
		'edit_users',
		'export',
		'import',
		'install_plugins',
		'install_themes',
		'list_users',
		'manage_categories',
		'manage_links',
		'manage_options',
		'manage_post_tags',
		'moderate_comments',
		'promote_users',
		'publish_pages',
		'publish_posts',
		'read',
		'read_private_pages',
		'read_private_posts',
		'remove_users',
		'switch_themes',
		'unfiltered_html',
		'unfiltered_upload',
		'update_core',
		'update_plugins',
		'update_themes',
		'upload_files',
	);
}

/**
 * Returns an array of roles that have a capability.
 *
 * @param string $cap
 *
 * @return array
 */
function wpum_get_cap_roles( $cap ) {
	global $wp_roles;

	$_roles = array();

	foreach ( $wp_roles->role_objects as $role ) {

		if ( $role->has_cap( $cap ) ) {
			$_roles[] = $role->name;
		}
	}

	return $_roles;
}

/**
 * @return array
 */
function wpum_get_capabilities() {
	// Apply filters to the array of capabilities.
	$capabilities = apply_filters( 'wpum_get_capabilities', array_keys( wpum_get_caps() ) );

	// Sort the capabilities alphabetically.
	sort( $capabilities );

	return array_unique( $capabilities );
}

/**
 * @return array
 * @global object $wp_roles
 */
function wpum_get_role_capabilities() {
	global $wp_roles;

	// Set up an empty capabilities array.
	$capabilities = array();

	// Loop through each role object because we need to get the caps.
	foreach ( $wp_roles->role_objects as $key => $role ) {

		// Make sure that the role has caps.
		if ( is_array( $role->capabilities ) ) {

			// Add each of the role's caps (both granted and denied) to the array.
			foreach ( $role->capabilities as $cap => $grant ) {
				$capabilities[ $cap ] = $cap;
			}
		}
	}

	// Return the capabilities array, making sure there are no duplicates.
	return array_unique( $capabilities );
}

/**
 * @param string $cap
 *
 * @return bool
 */
function wpum_check_for_cap( $cap = '' ) {

	// Without a capability, we have nothing to check for.  Just return false.
	if ( ! $cap ) {
		return false;
	}

	// Check if the cap is assigned to any role.
	return in_array( $cap, wpum_get_role_capabilities(), true );
}

/**
 * Return an array of capabilities that are not allowed on this installation.
 *
 * @return array
 */
function wpum_get_hidden_caps() {

	$caps = array();

	// This is always a hidden cap and should never be added to the caps list.
	$caps[] = 'do_not_allow';

	// Network-level caps.
	// These shouldn't show on single-site installs anyway.
	// On multisite installs, they should be handled by a network-specific role manager.
	$caps[] = 'create_sites';
	$caps[] = 'delete_sites';
	$caps[] = 'manage_network';
	$caps[] = 'manage_sites';
	$caps[] = 'manage_network_users';
	$caps[] = 'manage_network_plugins';
	$caps[] = 'manage_network_themes';
	$caps[] = 'manage_network_options';
	$caps[] = 'upgrade_network';

	// This cap is needed on single site to set up a multisite network.
	if ( is_multisite() ) {
		$caps[] = 'setup_network';
	}

	// Unfiltered uploads.
	if ( is_multisite() || ! defined( 'ALLOW_UNFILTERED_UPLOADS' ) || ! ALLOW_UNFILTERED_UPLOADS ) {
		$caps[] = 'unfiltered_upload';
	}

	// Unfiltered HTML.
	if ( is_multisite() || ( defined( 'DISALLOW_UNFILTERED_HTML' ) && DISALLOW_UNFILTERED_HTML ) ) {
		$caps[] = 'unfiltered_html';
	}

	// File editing.
	if ( is_multisite() || ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) ) {
		$caps[] = 'edit_files';
		$caps[] = 'edit_plugins';
		$caps[] = 'edit_themes';
	}

	// File mods.
	if ( is_multisite() || ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) ) {
		$caps[] = 'edit_files';
		$caps[] = 'edit_plugins';
		$caps[] = 'edit_themes';
		$caps[] = 'update_plugins';
		$caps[] = 'delete_plugins';
		$caps[] = 'install_plugins';
		$caps[] = 'upload_plugins';
		$caps[] = 'update_themes';
		$caps[] = 'delete_themes';
		$caps[] = 'install_themes';
		$caps[] = 'upload_themes';
		$caps[] = 'update_core';
	}

	return array_unique( $caps );
}

/**
 * Get rid of hidden capabilities.
 *
 * @param array $caps
 *
 * @return array
 */
function wpum_remove_hidden_caps( $caps ) {
	return apply_filters( 'wpum_remove_hidden_caps', true ) ? array_diff( $caps, wpum_get_hidden_caps() ) : $caps;
}

/**
 * Old WordPress levels system.  This is mostly useful for filtering out the levels when shown
 * in admin screen.  Plugins shouldn't rely on these levels to create permissions for users.
 * They should move to the newer system of checking for a specific capability instead.
 *
 * @return array
 * @since  0.1.0
 * @access public
 */
function wpum_get_old_levels() {
	return array(
		'level_0',
		'level_1',
		'level_2',
		'level_3',
		'level_4',
		'level_5',
		'level_6',
		'level_7',
		'level_8',
		'level_9',
		'level_10',
	);
}

/**
 * @param array $caps
 *
 * @return array
 */
function wpum_remove_old_levels( $caps ) {
	return apply_filters( 'wpum_remove_old_levels', true ) ? array_diff( $caps, wpum_get_old_levels() ) : $caps;
}

/**
 * @return array
 */
function wpum_new_role_default_capabilities() {
	return apply_filters( 'wpum_new_role_default_capabilities', array( 'read' ) );
}

/**
 * @return array
 */
function wpum_new_role_default_caps() {
	return apply_filters( 'wpum_new_role_default_caps', array( 'read' => true ) );
}

/**
 * Register cap groups
 */
function wpum_register_cap_groups() {
	do_action( 'wpum_register_cap_groups' );
}

/**
 * Registers the default cap groups
 */
function wpum_register_default_cap_groups() {
	// Registers the general group.
	wpum_register_cap_group( 'general', array(
		'label'    => esc_html__( 'General', 'members' ),
		'icon'     => 'dashicons-wordpress',
		'priority' => 5,
	) );

	// Loop through every custom post type.
	foreach ( get_post_types( array(), 'objects' ) as $type ) {

		// Skip revisions and nave menu items.
		if ( in_array( $type->name, array( 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset' ), true ) ) {
			continue;
		}

		// Get the caps for the post type.
		$has_caps = wpum_get_post_type_group_caps( $type->name );

		// Skip if the post type doesn't have caps.
		if ( empty( $has_caps ) ) {
			continue;
		}

		// Set the default post type icon.
		$icon = $type->hierarchical ? 'dashicons-admin-page' : 'dashicons-admin-post';

		// Get the post type icon.
		if ( is_string( $type->menu_icon ) && preg_match( '/dashicons-/i', $type->menu_icon ) ) {
			$icon = $type->menu_icon;
		} elseif ( 'attachment' === $type->name ) {
			$icon = 'dashicons-admin-media';
		} elseif ( 'download' === $type->name ) {
			$icon = 'dashicons-download';
		} elseif ( 'product' === $type->name ) {
			$icon = 'dashicons-cart';
		}

		// Register the post type cap group.
		wpum_register_cap_group( "type-{$type->name}", array(
			'label'    => $type->labels->name,
			'caps'     => $has_caps,
			'icon'     => $icon,
			'priority' => 10,
		) );
	}

	// Register the taxonomy group.
	wpum_register_cap_group( 'taxonomy', array(
		'label'      => esc_html__( 'Taxonomies', 'members' ),
		'caps'       => wpum_get_taxonomy_group_caps(),
		'icon'       => 'dashicons-tag',
		'diff_added' => true,
		'priority'   => 15,
	) );

	// Register the theme group.
	wpum_register_cap_group( 'theme', array(
		'label'    => esc_html__( 'Appearance', 'members' ),
		'icon'     => 'dashicons-admin-appearance',
		'priority' => 20,
	) );

	// Register the plugin group.
	wpum_register_cap_group( 'plugin', array(
		'label'    => esc_html__( 'Plugins', 'members' ),
		'icon'     => 'dashicons-admin-plugins',
		'priority' => 25,
	) );

	// Register the user group.
	wpum_register_cap_group( 'user', array(
		'label'    => esc_html__( 'Users', 'members' ),
		'icon'     => 'dashicons-admin-users',
		'priority' => 30,
	) );

	// Register the custom group.
	wpum_register_cap_group( 'custom', array(
		'label'      => esc_html__( 'Custom', 'members' ),
		'caps'       => wpum_get_capabilities(),
		'icon'       => 'dashicons-admin-generic',
		'diff_added' => true,
		'priority'   => 995,
	) );
}

/**
 * Returns the instance of cap group registry.
 *
 * @return WPUM_Collection
 */
function wpum_cap_group_collection() {
	return WPUM_Collection::get_instance( 'cap_group' );
}

/**
 * Function for registering a cap group.
 *
 * @param string $name
 * @param array  $args
 */
function wpum_register_cap_group( $name, $args = array() ) {
	wpum_cap_group_collection()->register( $name, new WPUM_Capability_Group( $name, $args ) );
}

/**
 * Unregisters a group.
 *
 * @param string $name
 */
function wpum_unregister_cap_group( $name ) {
	wpum_cap_group_collection()->unregister( $name );
}

/**
 * Checks if a group exists.
 *
 * @param string $name
 *
 * @return bool
 */
function wpum_cap_group_exists( $name ) {
	return wpum_cap_group_collection()->exists( $name );
}

/**
 * Returns an array of registered group objects.
 *
 * @return array
 */
function wpum_get_cap_groups() {
	return wpum_cap_group_collection()->get_items();
}

/**
 * Returns a group object if it exists.  Otherwise, `FALSE`.
 *
 * @param string $name
 *
 * @return object|bool
 */
function wpum_get_cap_group( $name ) {
	return wpum_cap_group_collection()->get( $name );
}

/**
 * Returns the caps for a specific post type capability group.
 *
 * @param string $post_type
 *
 * @return array
 */
function wpum_get_post_type_group_caps( $post_type = 'post' ) {
	// Get the post type caps.
	$caps = (array) get_post_type_object( $post_type )->cap;

	// remove meta caps.
	unset( $caps['edit_post'] );
	unset( $caps['read_post'] );
	unset( $caps['delete_post'] );

	// Get the cap names only.
	$caps = array_values( $caps );

	// If this is not a core post/page post type.
	if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) {

		// Get the post and page caps.
		$post_caps = array_values( (array) get_post_type_object( 'post' )->cap );
		$page_caps = array_values( (array) get_post_type_object( 'page' )->cap );

		// Remove post/page caps from the current post type caps.
		$caps = array_diff( $caps, $post_caps, $page_caps );
	}

	// If attachment post type, add the `unfiltered_upload` cap.
	if ( 'attachment' === $post_type ) {
		$caps[] = 'unfiltered_upload';
	}

	$registered_caps = array_keys( wp_list_filter( wpum_get_caps(), array( 'group' => "type-{$post_type}" ) ) );

	if ( $registered_caps ) {
		array_merge( $caps, $registered_caps );
	}

	// Make sure there are no duplicates and return.
	return array_unique( $caps );
}

/**
 * Returns the caps for the taxonomy capability group.
 *
 * @return array
 * @since  1.0.0
 * @access public
 */
function wpum_get_taxonomy_group_caps() {

	$do_not_add = array(
		'assign_categories',
		'edit_categories',
		'delete_categories',
		'assign_post_tags',
		'edit_post_tags',
		'delete_post_tags',
		'manage_post_tags',
	);

	$taxi = get_taxonomies( array(), 'objects' );

	$caps = array();

	foreach ( $taxi as $tax ) {
		$caps = array_merge( $caps, array_values( (array) $tax->cap ) );
	}

	$registered_caps = array_keys( wp_list_filter( wpum_get_caps(), array( 'group' => 'taxonomy' ) ) );

	if ( $registered_caps ) {
		array_merge( $caps, $registered_caps );
	}

	return array_diff( array_unique( $caps ), $do_not_add );
}

/**
 * Returns the caps for the custom capability group.
 *
 * @return array
 * @since  1.0.0
 * @access public
 */
function wpum_get_custom_group_caps() {
	$caps = wpum_get_capabilities();

	$registered_caps = array_keys( wp_list_filter( wpum_get_caps(), array( 'group' => 'custom' ) ) );

	if ( $registered_caps ) {
		array_merge( $caps, $registered_caps );
	}

	return array_unique( $caps );
}
