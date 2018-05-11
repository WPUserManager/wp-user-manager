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
function wpum_load_admin_scripts() {

	$screen = get_current_screen();

	$allowed_screens = [
		'users_page_wpum-settings',
	];

	wp_register_script( 'wpum-vue-manifest', WPUM_PLUGIN_URL . 'dist/static/js/manifest.js' , array(), WPUM_VERSION, true );
	wp_register_script( 'wpum-vue-vendor', WPUM_PLUGIN_URL . 'dist/static/js/vendor.js' , array(), WPUM_VERSION, true );

	if( in_array( $screen->base, $allowed_screens ) ) {
		wp_enqueue_style( 'wpum-logo', WPUM_PLUGIN_URL . 'assets/css/admin/wpum-logo.css', array(), WPUM_VERSION );
	}

	wp_enqueue_script( 'wpum-upgrades', WPUM_PLUGIN_URL . 'assets/js/admin/admin-upgrades.min.js' , array(), WPUM_VERSION, true );
	wp_enqueue_style( 'wpum-upgrades-style', WPUM_PLUGIN_URL . 'assets/css/admin/upgrades.css' , array(), WPUM_VERSION );

	$js_vars = [
		'updates' => array(
			'ajax_error' => __( 'Please reload this page and try again' ),
		),
		'db_update_confirmation_msg_button' => __( 'Run Updates' ),
		'db_update_confirmation_msg'        => __( 'The following process will make updates to your site\'s database. Please create a database backup before proceeding with updates.' ),
		'error_message'                     => __( 'Something went wrong kindly try again!' ),
	];
	wp_localize_script( 'wpum-upgrades', 'wpum_vars', $js_vars );

}
add_action( 'admin_enqueue_scripts', 'wpum_load_admin_scripts' );

/**
 * Load WPUM scripts on the frontend.
 *
 * @return void
 */
function wpum_load_scripts() {

	// Load frontend styles.
	wp_enqueue_style( 'wpum-frontend', WPUM_PLUGIN_URL . 'assets/css/wpum.min.css', array(), WPUM_VERSION );

	// Load frontend js.
	// At the moment the only js required is on the account page.
	if( is_page( wpum_get_core_page_id( 'account' ) ) ) {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'wpum-frontend-js', WPUM_PLUGIN_URL . 'assets/js/wp-user-manager.min.js' , array( 'jquery' ), WPUM_VERSION, true );
	}

}
add_action( 'wp_enqueue_scripts', 'wpum_load_scripts' );
