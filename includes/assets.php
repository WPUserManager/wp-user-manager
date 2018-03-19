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
 * Load WPUM scripts on the frontend.
 *
 * @return void
 */
function wpum_load_scripts() {

	// Determine wether vuejs is running in dev mode.
	// If so, load all .js files into the "src" folder from the webpack server.
	$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

	if( $is_vue_dev ) {
		$vuefiles = array();
		foreach ( glob( WPUM_PLUGIN_DIR . 'src/*.js' ) as $file ) {
			$vuefiles[] = basename( $file );
		}
		foreach( $vuefiles as $jsfile ) {
			wp_register_script( $jsfile, 'http://localhost:8080/' . $jsfile, array(), WPUM_VERSION, true );
			wp_enqueue_script( $jsfile );
		}
	}

	// Load frontend styles and scripts.
	wp_enqueue_style( 'wpum-frontend', WPUM_PLUGIN_URL . 'assets/css/wpum.min.css', array(), WPUM_VERSION );

}
add_action( 'wp_enqueue_scripts', 'wpum_load_scripts' );

/**
 * Load assets into the admin panel.
 *
 * @return void
 */
function wpum_load_admin_scripts() {

	$screen = get_current_screen();

	if( $screen->base == 'users' ) {
		wp_enqueue_style( 'wpum-registration-status', WPUM_PLUGIN_URL . 'assets/css/admin/registration-status.css', array(), WPUM_VERSION );
	}

}
add_action( 'admin_enqueue_scripts', 'wpum_load_admin_scripts' );
