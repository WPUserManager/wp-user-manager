<?php
/**
 * Actions meant to be triggered everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Delete cached list of pages when a page is updated or created.
 * This is needed to refresh the list of available pages for the options panel.
 *
 * @param string $post_id
 * @return void
 */
function wpum_delete_pages_transient( $post_id ) {

	if ( wp_is_post_revision( $post_id ) )
		return;

	delete_transient( 'wpum_get_pages' );

}
add_action( 'save_post_page', 'wpum_delete_pages_transient' );

/**
 * Add WPUM specific admin bar links.
 *
 * @param object $wp_admin_bar
 * @return void
 */
function wpum_admin_bar_menu( $wp_admin_bar ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$args = array(
		'id'    => 'wpum_node',
		'href'  => admin_url( 'users.php' ),
		'title' => '<span class="ab-icon dashicons dashicons-admin-users" style="margin-top:2px"></span>' . esc_html__( ' Users' ),
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_emails',
		'href'   => admin_url( 'users.php?page=wpum-emails' ),
		'title'  => esc_html__( 'Emails' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_custom_fields',
		'href'   => admin_url( 'users.php?page=wpum-custom-fields' ),
		'title'  => esc_html__( 'Custom fields' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_registration_forms',
		'href'   => admin_url( 'users.php?page=wpum-registration-forms' ),
		'title'  => esc_html__( 'Registration forms' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_settings',
		'href'   => admin_url( 'users.php?page=wpum-settings' ),
		'title'  => esc_html__( 'Settings' ),
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

	if( ! empty( $excluded_roles ) && is_user_logged_in() && in_array( $user->roles[0], $excluded_roles ) && ! is_admin() ) {
		if ( current_user_can( $user->roles[0] ) ) {
			show_admin_bar( false );
		}
	}
}
add_action( 'after_setup_theme', 'wpum_remove_admin_bar' );

/**
 * Restrict access to the wp-login.php registration page
 * and redirect to the WPUM registration page.
 *
 * @return void
 */
function wpum_restrict_wp_registration() {

	$registration_redirect = wpum_get_option( 'wp_login_signup_redirect' );

	if( $registration_redirect ) {
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

	if( $password_redirect ) {
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
 * Lock access to wp-login.php and redirect users to the WPUM login page.
 *
 * @return void
 */
function wpum_restrict_wplogin() {

	global $pagenow;

	if( 'wp-login.php' == $pagenow ) {
		$login_page = wpum_get_core_page_id( 'login' );
		if( $login_page && wpum_get_option( 'lock_wplogin' ) && ! isset( $_GET['action'] ) ) {
			wp_safe_redirect( esc_url( get_permalink( $login_page[0] ) ) );
			exit;
		}
	}

}
// add_action( 'init', 'wpum_restrict_wplogin' );

/**
 * Setup the query argument to detect the currently active account page tab.
 *
 * @return string
 */
function wpum_account_page_set_active_tab() {

	if( ! is_page( wpum_get_core_page_id( 'account' ) ) && ! is_user_logged_in() ) {
		return;
	}

	$tabs      = wpum_get_account_page_tabs();
	$first_tab = key( $tabs );
	$step      = isset( $_GET['tab'] ) && array_key_exists( $_GET['tab'], $tabs ) ? sanitize_key( $_GET['tab'] ) : $first_tab;

	set_query_var( 'tab', $step );

}
add_action( 'wp_head', 'wpum_account_page_set_active_tab' );

/**
 * Display the appropriate content for the account page given the currently active tab.
 *
 * @return void
 */
function wpum_display_account_page_content() {

	$active_tab = get_query_var('tab');

	if( $active_tab == 'account' || $active_tab == 'password' ) {
		echo WPUM()->forms->get_form( $active_tab );
	} else {
		do_action( 'wpum_account_page_content_' . $active_tab );
	}

}
add_action( 'wpum_account_page_content', 'wpum_display_account_page_content' );
