<?php
/**
 * Options management functions.
 *
 * @package     wp-user-manager
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
 * @param string  $key
 * @param boolean $default
 *
 * @return mixed
 */
function wpum_get_option( $key = '', $default = false ) {
	global $wpum_options;
	$value = ! empty( $wpum_options[ $key ] ) ? $wpum_options[ $key ] : $default;
	$value = apply_filters( 'wpum_get_option', $value, $key, $default );

	return apply_filters( 'wpum_get_option_' . $key, $value, $key, $default );
}

/**
 * Update an option
 *
 * Updates an wpum setting value in both the db and the global variable.
 * Warning: Passing in an empty, false or null string value will remove
 *          the key from the wpum_options array.
 *
 * @param string          $key   The Key to update
 * @param string|bool|int $value The value to set the key to
 *
 * @return boolean True if updated, false if not.
 */
function wpum_update_option( $key = '', $value = false ) {

	// If no key, exit.
	if ( empty( $key ) ) {
		return false;
	}

	if ( empty( $value ) ) {
		$remove_option = wpum_delete_option( $key );

		return $remove_option;
	}

	// First let's grab the current settings.
	$options = get_option( 'wpum_settings' );

	// Let's let devs alter that value coming in.
	$value = apply_filters( 'wpum_update_option', $value, $key );

	// Next let's try to update the value.
	$options[ $key ] = $value;
	$did_update      = update_option( 'wpum_settings', $options );

	// If it updated, let's update the global variable.
	if ( $did_update ) {
		global $wpum_options;
		$wpum_options         = is_array( $wpum_options ) ? $wpum_options : array();
		$wpum_options[ $key ] = $value;
	}

	return $did_update;
}

/**
 * Remove an option
 *
 * Removes a wpum setting value in both the db and the global variable.
 *
 * @param string $key The Key to delete
 *
 * @return boolean True if updated, false if not.
 */
function wpum_delete_option( $key = '' ) {
	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}
	// First let's grab the current settings
	$options = get_option( 'wpum_settings' );
	// Next let's try to update the value
	if ( isset( $options[ $key ] ) ) {
		unset( $options[ $key ] );
	}
	$did_update = update_option( 'wpum_settings', $options );
	// If it updated, let's update the global variable
	if ( $did_update ) {
		global $wpum_options;
		$wpum_options = $options;
	}

	return $did_update;
}
