<?php
/**
 * Register all scripts and styles for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	wp_register_script( 'wpum-vue-manifest', WPUM_PLUGIN_URL . 'dist/static/js/manifest.js', array(), WPUM_VERSION, true );
	wp_register_script( 'wpum-vue-vendor', WPUM_PLUGIN_URL . 'dist/static/js/vendor.js', array(), WPUM_VERSION, true );

	if ( in_array( $screen->base, $allowed_screens ) ) {
		wp_enqueue_script( 'wpum-settings', WPUM_PLUGIN_URL . 'assets/js/admin/settings.min.js', array(), WPUM_VERSION, true );
		wp_enqueue_style( 'wpum-logo', WPUM_PLUGIN_URL . 'assets/css/admin/wpum-logo.css', array(), WPUM_VERSION );
		wp_localize_script( 'wpum-settings', 'wpum_settings', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
		) );
	}

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
	wp_enqueue_script( 'jquery' );
	wp_register_script( 'wpum-directories', WPUM_PLUGIN_URL . 'assets/js/wpum-directories.min.js', array( 'jquery' ), WPUM_VERSION, true );

	if ( is_page( wpum_get_core_page_id( 'account' ) ) || is_page( wpum_get_core_page_id( 'register' ) ) ) {
		wpum_enqueue_scripts();
	}

}
add_action( 'wp_enqueue_scripts', 'wpum_load_scripts' );

function wpum_enqueue_scripts() {
	wp_enqueue_style( 'wpum-select2-style', WPUM_PLUGIN_URL . 'assets/css/vendor/select2.min.css', false, WPUM_VERSION );
	wp_enqueue_script( 'wpum-select2', WPUM_PLUGIN_URL . 'assets/js/vendor/select2.min.js', array( 'jquery' ), WPUM_VERSION, true );
	wp_enqueue_script( 'wpum-datepicker', WPUM_PLUGIN_URL . 'assets/js/vendor/flatpickr.min.js', array( 'jquery' ), WPUM_VERSION, true );
	wp_enqueue_style( 'wpum-datepicker-style', WPUM_PLUGIN_URL . 'assets/css/vendor/flatpickr.min.css', false, WPUM_VERSION );

	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	wp_enqueue_script( 'wpum-frontend-js', WPUM_PLUGIN_URL . 'assets/js/wp-user-manager' . $suffix . '.js', array( 'jquery' ), WPUM_VERSION, true );

	$js_variables = [
		'dateFormat' => apply_filters( 'wpum_field_datepicker_date_format', get_option( 'date_format' ) ),
	];

	wp_localize_script( 'wpum-frontend-js', 'wpumFrontend', $js_variables );

	do_action( 'wpum_enqueue_frontend_scripts', $suffix );
}
