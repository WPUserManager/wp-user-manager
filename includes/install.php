<?php
/**
 * Install function.
 *
 * @package     wp-user-manager
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Runs on plugin install by setting up the post types, custom taxonomies, flushing rewrite rules to initiate the new
 * slugs and also creates the plugin and populates the settings fields for those plugin pages.
 *
 * @param boolean $network_wide
 * @return void
 */
function wp_user_manager_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {
		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
			switch_to_blog( $blog_id );
			wpum_run_install();
			restore_current_blog();
		}
	} else {
		wpum_run_install();
	}

}

/**
 * Generates core pages and updates settings panel with the newly created pages.
 *
 * @since 1.0.0
 * @return void
 */
function wpum_generate_pages() {
	// Generate login page
	if ( ! wpum_get_option( 'login_page' ) ) {
		$login = wp_insert_post(
			array(
				'post_title'     => __( 'Login', 'wpum' ),
				'post_content'   => '[wpum_login_form psw_link="yes" register_link="yes"]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'login_page', [ $login ] );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'password_recovery_page' ) ) {
		$psw = wp_insert_post(
			array(
				'post_title'     => __( 'Password Reset', 'wpum' ),
				'post_content'   => '[wpum_password_recovery login_link="yes" register_link="yes"]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'password_recovery_page', [ $psw ] );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'registration_page' ) ) {
		$register = wp_insert_post(
			array(
				'post_title'     => __( 'Register', 'wpum' ),
				'post_content'   => '[wpum_register login_link="yes" psw_link="yes"]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'registration_page', [ $register ] );
	}
	// Generate account page
	if ( ! wpum_get_option( 'account_page' ) ) {
		$account = wp_insert_post(
			array(
				'post_title'     => __( 'Account', 'wpum' ),
				'post_content'   => '[wpum_account]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'account_page', [ $account ] );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'profile_page' ) ) {
		$profile = wp_insert_post(
			array(
				'post_title'     => __( 'Profile', 'wpum' ),
				'post_content'   => '[wpum_profile]',
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed'
			)
		);
		wpum_update_option( 'profile_page', [ $profile ] );
	}
}

/**
 * Run the installation process of the plugin.
 *
 * @return void
 */
function wpum_run_install() {

	// Enable registrations on the site.
	update_option( 'users_can_register', true );

	// Store plugin installation date.
	add_option( 'wpum_activation_date', strtotime( "now" ) );

	// Add Upgraded From Option.
	$current_version = get_option( 'wpum_version' );
	if ( $current_version ) {
		update_option( 'wpum_version_upgraded_from', $current_version );
	}

	// Update current version.
	update_option( 'wpum_version', WPUM_VERSION );

	// Install default pages
	wpum_generate_pages();

	wpum_install_default_field_group();

	wpum_install_fields();

	wpum_install_cover_image_field();

	wpum_setup_default_custom_search_fields();

	// Clear the permalinks.
	flush_rewrite_rules();

	// Setup permalinks for WPUM.
	update_option( 'wpum_permalink', 'username' );

	// Add the transient to redirect.
	set_transient( '_wpum_activation_redirect', true, 30 );

}
