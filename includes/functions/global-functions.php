<?php
/**
 * Functions that can be used everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve the ID of a WPUM core page.
 *
 * @param string $page Available core pages are login, register, password, account, profile.
 * @return int $page_id the ID of the requested page.
 */
function wpum_get_core_page_id( $page = null ) {

	if( ! $page ) {
		return;
	}

	$id = null;

	switch( $page ) {
		case 'login':
			$id = wpum_get_option( 'login_page' );
			break;
		case 'register':
			$id = wpum_get_option( 'registration_page' );
			break;
		case 'password':
			$id = wpum_get_option( 'password_recovery_page' );
			break;
		case 'account':
			$id = wpum_get_option( 'account_page' );
			break;
		case 'profile':
			$id = wpum_get_option( 'profile_page' );
			break;
	}

	$id = is_array( $id ) ? $id[0] : false;

	return $id;

}

/**
 * Pluck a certain field out of each object in a list.
 *
 * This has the same functionality and prototype of
 * array_column() (PHP 5.5) but also supports objects.
 *
 * @param array      $list      List of objects or arrays
 * @param int|string $field     Field from the object to place instead of the entire object
 * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
 *                              Default null.
 *
 * @return array Array of found values. If `$index_key` is set, an array of found values with keys
 *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
 *               `$list` will be preserved in the results.
 */
function wpum_list_pluck( $list, $field, $index_key = null ) {
	if ( ! $index_key ) {
		/**
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			if ( is_object( $value ) ) {
				if ( isset( $value->$field ) ) {
					$list[ $key ] = $value->$field;
				}
			} else {
				if ( isset( $value[ $field ] ) ) {
					$list[ $key ] = $value[ $field ];
				}
			}
		}
		return $list;
	}
	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();
	foreach ( $list as $value ) {
		if ( is_object( $value ) ) {
			if ( isset( $value->$index_key ) ) {
				$newlist[ $value->$index_key ] = $value->$field;
			} else {
				$newlist[] = $value->$field;
			}
		} else {
			if ( isset( $value[ $index_key ] ) ) {
				$newlist[ $value[ $index_key ] ] = $value[ $field ];
			} else {
				$newlist[] = $value[ $field ];
			}
		}
	}
	$list = $newlist;
	return $list;
}

/**
 * Retrieve the correct label for the login form.
 *
 * @return string
 */
function wpum_get_login_label() {

	$label        = esc_html__( 'Username' );
	$login_method = wpum_get_option( 'login_method' );

	if( $login_method == 'email' ) {
		$label = esc_html__( 'Email' );
	} elseif( $login_method == 'username_email' ) {
		$label = esc_html__( 'Username or email' );
	}

	return $label;

}
