<?php
/**
 * Actions meant to be triggered everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

use Carbon_Fields\Container;
use Carbon_Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delete cached list of pages when a page is updated or created.
 * This is needed to refresh the list of available pages for the options panel.
 *
 * @param string $post_id
 * @return void
 */
function wpum_delete_pages_transient( $post_id ) {

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	delete_transient( 'wpum_get_pages' );

}
add_action( 'save_post_page', 'wpum_delete_pages_transient' );

/**
 * Delete cached list of pages when a page is deleted.
 *
 * @param int $post_id
 * @param WP_Post $post
 * @return void
 */
function wpum_delete_pages_transient_on_delete( $post_id, $post ) {

	if ( wp_is_post_revision( $post_id ) || $post->post_type !== 'page' ) {
		return;
	}

	delete_transient( 'wpum_get_pages' );

}
add_action( 'delete_post', 'wpum_delete_pages_transient_on_delete', 99, 2 );

/**
 * Add WPUM specific admin bar links.
 *
 * @param object $wp_admin_bar
 * @return void
 */
function wpum_admin_bar_menu( $wp_admin_bar ) {

	if ( ! current_user_can( apply_filters( 'wpum_admin_pages_capability', 'manage_options' ) ) ) {
		return;
	}

	$args = array(
		'id'    => 'wpum_node',
		'href'  => admin_url( 'users.php' ),
		'title' => '<span class="ab-icon dashicons dashicons-admin-users" style="margin-top:2px"></span>' . esc_html__( ' Users', 'wp-user-manager' ),
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_emails',
		'href'   => admin_url( 'users.php?page=wpum-emails' ),
		'title'  => esc_html__( 'Emails', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_custom_fields',
		'href'   => admin_url( 'users.php?page=wpum-custom-fields' ),
		'title'  => esc_html__( 'Custom fields', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_registration_forms',
		'href'   => admin_url( 'users.php?page=wpum-registration-forms' ),
		'title'  => esc_html__( 'Registration Forms', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_settings',
		'href'   => admin_url( 'users.php?page=wpum-settings' ),
		'title'  => esc_html__( 'Settings', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

}
add_action( 'admin_bar_menu', 'wpum_admin_bar_menu', 100 );

/**
 * Hide admin bar from the frontend based on the selected roles.
 *
 * @return void
 */
function wpum_remove_admin_bar() {
	$excluded_roles = wpum_get_option( 'adminbar_roles' );
	$user           = wp_get_current_user();

	if ( ! empty( $excluded_roles ) && is_user_logged_in() && in_array( $user->roles[0], $excluded_roles ) && ! is_admin() ) {
		if ( current_user_can( $user->roles[0] ) ) {
			show_admin_bar( false );
		}
	}
}
add_action( 'after_setup_theme', 'wpum_remove_admin_bar' );

function wpum_restrict_wp_admin_dashboard_access() {
	if ( ! is_admin() ) {
		return;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( current_user_can( 'administrator' ) ) {
		return;
	}

	$excluded_roles = wpum_get_option( 'wp_admin_roles' );
	if ( empty( $excluded_roles ) ) {
		return;
	}

	$user = wp_get_current_user();
	if ( ! in_array( $user->roles[0], $excluded_roles ) ) {
		return;
	}

	$redirect = apply_filters( 'wpum_restrict_wp_admin_dashboard_access_redirect', home_url() );

	nocache_headers();
	wp_safe_redirect( $redirect );
	exit;
}

add_action( 'init', 'wpum_restrict_wp_admin_dashboard_access' );

/**
 * Restrict access to the wp-login.php registration page
 * and redirect to the WPUM registration page.
 *
 * @return void
 */
function wpum_restrict_wp_registration() {

	$registration_redirect = wpum_get_option( 'wp_login_signup_redirect' );

	if ( $registration_redirect ) {
		wp_safe_redirect( esc_url( get_permalink( $registration_redirect[0] ) ) );
		exit;
	}

}
add_action( 'login_form_register', 'wpum_restrict_wp_registration' );

/**
 * Restrict access to wp-login.php?action=lostpassword
 *
 * @return void
 */
function wpum_restrict_wp_lostpassword() {

	$password_redirect = wpum_get_option( 'wp_login_password_redirect' );

	if ( $password_redirect ) {
		wp_safe_redirect( esc_url( get_permalink( $password_redirect[0] ) ) );
		exit;
	}

}
add_action( 'login_form_lostpassword', 'wpum_restrict_wp_lostpassword' );

/**
 * Restrict access to WordPress admin profile.
 *
 * @return void
 */
function wpum_restrict_wp_profile() {

	$profile_redirect = wpum_get_option( 'backend_profile_redirect' );

	if ( ! current_user_can( 'administrator' ) && IS_PROFILE_PAGE && $profile_redirect ) {
		wp_safe_redirect( esc_url( get_permalink( $profile_redirect[0] ) ) );
		exit;
	}

}
add_action( 'load-profile.php', 'wpum_restrict_wp_profile' );

/**
 * Restrict access to the account page only to logged in users.
 * After login, redirect visitors back to the account page.
 *
 * @return void
 */
function wpum_restrict_account_page() {

	$account_page = wpum_get_core_page_id( 'account' );
	$login_page   = wpum_get_core_page_id( 'login' );

	if ( $account_page && is_page( $account_page ) && ! is_user_logged_in() && $login_page ) {

		$redirect = get_permalink( $login_page );
		$redirect = add_query_arg(
			[
				'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', get_permalink() ),
			],
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;

	}

}
add_action( 'template_redirect', 'wpum_restrict_account_page' );

/**
 * Display the appropriate content for the account page given the currently active tab.
 *
 * @return void
 */
function wpum_display_account_page_content() {

	$active_tab = get_query_var( 'tab' );
	$tabs       = wpum_get_account_page_tabs();

	if ( empty( $active_tab ) ) {
		$active_tab = key( $tabs );
	}

	if ( in_array( $active_tab, array( 'settings', 'password', 'privacy' ) ) ) {
		if ( $active_tab == 'settings' ) {
			$active_tab = 'profile';
		}
		echo WPUM()->forms->get_form( $active_tab );
	} else {
		do_action( 'wpum_account_page_content_' . $active_tab );
	}

}
add_action( 'wpum_account_page_content', 'wpum_display_account_page_content' );

/**
 * Make nickname unique.
 *
 * @param int $user_id
 * @return void
 */
function wpum_check_display_name( $user_id ) {

	global $wpdb;

	// Getting user data and user meta data.
	$err['display'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE display_name = %s AND ID <> %d", $_POST['display_name'], $_POST['user_id'] ) );
	$err['nick']    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname' AND meta.meta_value = %s AND users.ID <> %d", $_POST['nickname'], $_POST['user_id'] ) );

	foreach ( $err as $key => $e ) {
		if ( $e >= 1 ) {
			add_action( 'user_profile_update_errors', "wpum_check_{$key}_field", 10, 3 );
		}
	}
}
add_action( 'personal_options_update', 'wpum_check_display_name' );
add_action( 'edit_user_profile_update', 'wpum_check_display_name' );

/**
 * Trigger the unique error for the display field.
 *
 * @return void
 */
function wpum_check_display_field( $errors, $update, $user ) {
	$errors->add( 'display_name_error', esc_html__( 'This display name is already in use by someone else. Display names must be unique.', 'wp-user-manager' ) );
}

/**
 * Trigger the unique error for the nickname field.
 *
 * @return void
 */
function wpum_check_nick_field( $errors, $update, $user ) {
	$errors->add( 'display_nick_error', esc_html__( 'This nickname is already in use by someone else. Nicknames must be unique.', 'wp-user-manager' ) );
}

/**
 * Add a "view profile" link to the admin user table.
 *
 * @param  array  $actions     list of actions
 * @param  object $user_object user details
 * @return array              list of actions
 */
function wpum_admin_user_action_link( $actions, $user_object ) {
	if ( wpum_get_core_page_id( 'profile' ) ) {
		$actions['view_profile'] = '<a href="' . esc_url( wpum_get_profile_url( $user_object ) ) . '" target="_blank">' . esc_html__( 'View Profile', 'wp-user-manager' ) . '</a>';
	}
	return $actions;
}
add_filter( 'user_row_actions', 'wpum_admin_user_action_link', 10, 2 );

/**
 * Complete setup of the plugin once first loaded.
 *
 * @return void
 */
function wpum_complete_setup() {
	if ( ! get_option( 'wpum_setup_is_complete' ) && ! get_option( 'wpum_version_upgraded_from' ) ) {

		wpum_install_default_field_group();

		$fields = wpum_install_fields();

		wpum_install_cover_image_field();

		wpum_setup_default_custom_search_fields();

		wpum_install_registration_form( $fields );

		update_option( 'wpum_setup_is_complete', true );

	}
}

/**
 * Prevent access to wp-login.php
 *
 * @return void
 */
function wpum_prevent_wp_login() {
	global $pagenow;

	$action        = ( isset( $_GET['action'] ) ) ? $_GET['action'] : '';
	$wpum_override = isset( $_GET['wpum_override'] ) ? $_GET['wpum_override'] : '';

	if ( $pagenow == 'wp-login.php' && ! $wpum_override && ( ! $action || ( $action && ! in_array( $action, array( 'logout', 'lostpassword', 'rp', 'resetpass', 'postpass' ) ) ) ) ) {
		$page = wp_login_url();
		wp_safe_redirect( $page );
		exit();
	}
}

if ( wpum_get_option( 'lock_wplogin' ) ) {
	add_action( 'init', 'wpum_prevent_wp_login' );
}

/**
 * Prevent acccess to site unless logged in
 *
 * @return void
 */
function wpum_prevent_entire_site() {
	if ( wp_doing_cron() || wp_doing_ajax() ) {
		return;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	global $pagenow;

	$login_page      = wp_login_url();
	$wp_login_locked = wpum_get_option( 'lock_wplogin' );
	$is_wp_login     = $pagenow == 'wp-login.php';

	if ( home_url( $_SERVER['REQUEST_URI'] ) == $login_page || ( $is_wp_login && ( ! empty( $_GET['wpum_override'] ) || ! $wp_login_locked ) ) ) {
		return;
	}

	if ( isset( $_POST['wp-submit'] ) && isset( $_POST['log'] ) ) {
		return;
	}

	$password_reset_page_id = wpum_get_core_page_id( 'password' );
	if ( ! empty( $password_reset_page_id ) ) {
		$password_reset_page = get_permalink( $password_reset_page_id );
		if ( 0 === strpos( home_url( $_SERVER['REQUEST_URI'] ), $password_reset_page ) ) {
			return;
		}
	}

	if ( wpum_get_option( 'lock_complete_site_allow_register' ) ) {
		$registration_pages   = array();
		$registration_pages[] = get_permalink( wpum_get_core_page_id( 'register' ) );
		foreach ( apply_filters( 'wpum_registration_pages', $registration_pages ) as $registration_page ) {
			if ( home_url( $_SERVER['REQUEST_URI'] ) == $registration_page ) {
				return;
			}
		}
	}

	if ( ! apply_filters( 'wpum_prevent_entire_site_access', true ) ) {
		return;
	}

	wp_safe_redirect( $login_page );
	exit();
}

if ( wpum_get_option( 'lock_complete_site' ) ) {
	add_action( 'init', 'wpum_prevent_entire_site', 9 );
}

/**
 * Finish data installation after the whole plugin has booted.
 *
 * @return void
 */
function wpum_finish_db_setup_after_plugin_init() {

	$upgrade = get_option( 'wpum_version_upgraded_from' );
	if ( ! $upgrade ) {
		wpum_complete_setup();
	}

}
add_action( 'after_wpum_init', 'wpum_finish_db_setup_after_plugin_init' );

function wpum_register_profile_privacy_fields() {
	global $pagenow;

	$roles = [];

	foreach ( wpum_get_roles( true, true ) as $role ) {
		$roles[ $role['value'] ] = $role['label'];
	}

	$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );

	$user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : false;

	$profileuser = isset( $user_id ) ? get_user_by( 'id', $user_id ) : false;
	$existing_roles = ( $profileuser ) ? $profileuser->roles : [];

	$fields = array(
		Field::make( 'checkbox', 'hide_profile_guests', esc_html__( 'Hide profile from guests', 'wp-user-manager' ) )
			->set_help_text( esc_html__( 'Hide this profile from guests. Overrides the global profile options.', 'wp-user-manager' ) ),
  		Field::make( 'checkbox', 'hide_profile_members', esc_html__( 'Hide profile from members', 'wp-user-manager' ) )
			->set_help_text( esc_html__( 'Hide this profile from members. Overrides the global profile options.', 'wp-user-manager' ) ),
	);

	if ( $allow_multiple_roles && ( $profileuser || $pagenow == 'user-new.php' ) && ! is_network_admin() ) {
		$fields[] = Field::make( 'multiselect', 'wpum_user_roles', '' )
		->add_options( $roles )
		->set_default_value( $existing_roles )
		->set_classes( 'wpum-multiple-user-roles' )
		->set_help_text( esc_html__( 'Select one or more roles for this user.', 'wp-user-manager' ) );
	}

	Container::make( 'user_meta', esc_html__( 'Profile Privacy', 'wp-user-manager' ) )
	        ->add_fields( $fields );
}

add_action( 'carbon_fields_register_fields', 'wpum_register_profile_privacy_fields' );

add_action( 'template_redirect', 'wpum_reset_password_redirect' );
function wpum_reset_password_redirect() {
	if ( ! isset( $_GET['action'] ) || 'wpum-reset' !== $_GET['action'] ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}


	if ( ! isset( $_GET['login'] ) || ! isset( $_GET['key'] ) ) {
		return;
	}

	list( $rp_path ) = explode( '?', wp_unslash( $_SERVER['REQUEST_URI'] ) );

	$value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
	setcookie( 'wpum-resetpass-' . COOKIEHASH, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

	$url = remove_query_arg( array( 'key', 'login', 'action' ) );
	$url = add_query_arg( 'step', 'reset', $url );

	wp_safe_redirect( $url );
	exit;
}

function wpum_action_profile_update( $userId, $oldUserData = [] ) {

	$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
	if ( ! $allow_multiple_roles ) {
		return;
	}

	if ( isset( $_POST['_wpum_user_roles'] ) && current_user_can( 'promote_user' ) ) {

		$user = get_user_by('ID', $userId);

		$wpum_roles = explode( '|', $_POST['_wpum_user_roles'] );
		wpum_update_roles( $wpum_roles, $user );
	}
}

add_action( 'profile_update', 'wpum_action_profile_update', 99, 2 );
if ( is_multisite() ) {
	add_action( 'add_user_to_blog', 'wpum_action_profile_update', 99 );
} else {
	add_action( 'user_register', 'wpum_action_profile_update', 99 );
}

function wpum_modify_multiple_roles_ui( $user ) {
	$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
	if ( ! $allow_multiple_roles ) {
		return;
	}

	echo <<<HTML
	<script>
		jQuery(function ( $ ) {
			if ( ! $( '.user-role-wrap select#role, #createuser select#role' ).length ) {
				return;
			}
			var el_userrole = $('.user-role-wrap select#role, #createuser select#role');
			$( $( '.wpum-multiple-user-roles' ) ).insertAfter( el_userrole ).css('padding', 0);
			$( el_userrole ).hide();
		});
	</script>
HTML;
}

add_action( 'user_new_form', 'wpum_modify_multiple_roles_ui', 0 );
add_action( 'show_user_profile', 'wpum_modify_multiple_roles_ui', 0 );
add_action( 'edit_user_profile', 'wpum_modify_multiple_roles_ui', 0 );

/**
 * Restrict profile page when disabled.
 *
 * @return void
 */
function wpum_restrict_profile_page() {
	$profile_page = wpum_get_core_page_id( 'profile' );

	if ( $profile_page && is_page( $profile_page ) && true === boolval( wpum_get_option( 'disable_profiles' ) ) ) {
		wp_safe_redirect( home_url() );
		die();
	}
}

add_action( 'template_redirect', 'wpum_restrict_profile_page' );

function wpum_flush_user_object_cache( $form, $values, $updated_user_id ) {
	wp_cache_delete( $updated_user_id, 'user_meta' );
}
add_action( 'wpum_after_custom_user_update', 'wpum_flush_user_object_cache', 100, 3 );
add_action( 'wpum_after_user_update', 'wpum_flush_user_object_cache', 100, 3 );

