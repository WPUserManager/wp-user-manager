<?php
/**
 * Register all scripts and styles for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Load the custom logo's css style within the admin panel when needed.
 *
 * @return void
 */
function wpum_load_admin_logo_style() {

	$screen = get_current_screen();

	$allowed_screens = [
		'users_page_wpum-settings',
	];

	if( in_array( $screen->base, $allowed_screens ) ) {
		wp_enqueue_style( 'wpum-logo', WPUM_PLUGIN_URL . 'assets/css/admin/wpum-logo.css', array(), WPUM_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'wpum_load_admin_logo_style' );

/**
 * Load WPUM scripts on the frontend.
 *
 * @return void
 */
function wpum_load_scripts() {

	// Load frontend styles.
	wp_enqueue_style( 'wpum-frontend', WPUM_PLUGIN_URL . 'assets/css/wpum.min.css', array(), WPUM_VERSION );

	// Load frontend js.
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'wpum-frontend-js', WPUM_PLUGIN_URL . 'assets/js/wp-user-manager.min.js' , array( 'jquery' ), WPUM_VERSION, true );

}
add_action( 'wp_enqueue_scripts', 'wpum_load_scripts' );
