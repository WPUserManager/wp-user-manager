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
	wp_register_script( 'wpum-vuejs-dev', 'http://localhost:8080/login.js', array(), WPUM_VERSION, true );
	wp_enqueue_script( 'wpum-vuejs-dev' );
}
add_action( 'wp_enqueue_scripts', 'wpum_load_scripts' );
