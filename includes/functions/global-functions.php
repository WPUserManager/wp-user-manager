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
