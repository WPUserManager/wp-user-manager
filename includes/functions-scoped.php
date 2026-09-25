<?php
/**
 * Functions that are used in addons but need to be scoped to the WPUM prefix
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       2.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'carbon_get_theme_option' ) ) {
	function carbon_get_theme_option() {
		return call_user_func_array( '\WPUM\carbon_get_theme_option', func_get_args() );
	}
}

if ( ! function_exists( 'carbon_get_term_meta' ) ) {
	function carbon_get_term_meta() {
		return call_user_func_array( '\WPUM\carbon_get_term_meta', func_get_args() );
	}
}

if ( ! function_exists( 'carbon_set_user_meta' ) ) {
	function carbon_set_user_meta() {
		return call_user_func_array( '\WPUM\carbon_set_user_meta', func_get_args() );
	}
}

if ( ! function_exists( 'carbon_get_user_meta' ) ) {
	function carbon_get_user_meta() {
		return call_user_func_array( '\WPUM\carbon_get_user_meta', func_get_args() );
	}
}

if ( ! function_exists( 'carbon_get_post_meta' ) ) {
	function carbon_get_post_meta() {
		return call_user_func_array( '\WPUM\carbon_get_post_meta', func_get_args() );
	}
}
