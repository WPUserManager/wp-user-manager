<?php
/**
 * Set of functions that deals with the options of the plugin.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve all options from WPUM.
 *
 * @return mixed
 */
function wpum_get_settings() {
	$settings = get_option( 'wpum_settings' );
	return apply_filters( 'wpum_get_settings', $settings );
}

/**
 * Retrieve an option from the database.
 * Returns default setting if nothing found.
 *
 * @param string $key
 * @param boolean $default
 * @return void
 */
function wpum_get_option( $key = '', $default = false ) {
	global $wpum_options;
	$value = ! empty( $wpum_options[ $key ] ) ? $wpum_options[ $key ] : $default;
	$value = apply_filters( 'wpum_get_option', $value, $key, $default );
	return apply_filters( 'wpum_get_option_' . $key, $value, $key, $default );
}
