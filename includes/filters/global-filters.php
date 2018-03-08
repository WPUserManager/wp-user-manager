<?php
/**
 * Filters meant to be triggered everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modify the url retrieved with wp_registration_url().
 *
 * @param string $url
 * @return void
 */
function wpum_set_registration_url( $url ) {
	$registration_page = wpum_get_core_page_id( 'register' );
	if ( $registration_page ) {
		return esc_url( get_permalink( $registration_page ) );
	} else {
		return $url;
	}
}
add_filter( 'register_url', 'wpum_set_registration_url' );

/**
 * Modify the url of the wp_lostpassword_url() function.
 *
 * @param string $url
 * @param string $redirect
 * @return void
 */
function wpum_set_lostpassword_url( $url, $redirect ) {

	$password_page = wpum_get_core_page_id( 'password' );

	if ( $password_page ) {
		return esc_url( get_permalink( $password_page ) );
	} else {
		return $url;
	}

}
add_filter( 'lostpassword_url', 'wpum_set_lostpassword_url', 10, 2 );

/**
 * Modify the logout url to include redirects set by WPUM - if any.
 *
 * @param string $logout_url
 * @param string $redirect
 * @return void
 */
function wpum_set_logout_url( $logout_url, $redirect ) {

	$logout_redirect = wpum_get_option( 'logout_redirect' );

	if ( ! empty( $logout_redirect ) && is_array( $logout_redirect ) && ! $redirect ) {
		$logout_redirect = get_permalink( $logout_redirect[0] );

		$args = [
			'action'      => 'logout',
			'redirect_to' => $logout_redirect
		];

		$logout_url = add_query_arg( $args, site_url( 'wp-login.php', 'login' ) );
		$logout_url = wp_nonce_url( $logout_url, 'log-out' );

	}

	return $logout_url;

}
add_filter( 'logout_url', 'wpum_set_logout_url', 20, 2 );

/**
 * Validate authentication with the selected login method.
 *
 * @param object $user
 * @param string $username
 * @param string $password
 * @return void
 */
function wpum_authentication( $user, $username, $password ) {

	$authentication_method = wpum_get_option( 'login_method' );

	if( $authentication_method == 'username' ) {

		if( is_email( $username ) ) {
			return new WP_Error( 'username_only', __( 'Invalid username or incorrect password.' ) );
		}

		return wp_authenticate_username_password( null, $username, $password );

	} elseif( $authentication_method == 'email' ) {

		if( ! empty( $username ) && is_email( $username ) ) {

			$user = get_user_by( 'email', $username );

			if ( isset( $user, $user->user_login, $user->user_status ) && 0 == (int) $user->user_status ) {
				$username = $user->user_login;
				return wp_authenticate_username_password( null, $username, $password );
			}

		} else {

			return new WP_Error( 'email_only', __( 'Invalid email address or incorrect password.' ) );

		}

	}

	return $user;

}
add_filter( 'authenticate', 'wpum_authentication', 20, 3 );
