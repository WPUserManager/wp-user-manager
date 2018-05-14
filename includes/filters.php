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
 * Modify the WordPress admin footer within WPUM powered pages.
 *
 * @param string $footer_text original text.
 * @return string
 */
function wpum_admin_rate_us( $footer_text ) {
	$screen = get_current_screen();
	if ( $screen->base !== 'users_page_wpum-settings' )
		return;
	$rate_text = sprintf( __( 'Please support the future of <a href="%1$s" target="_blank">WP User Manager</a> by <a href="%2$s" target="_blank">rating us</a> on <a href="%2$s" target="_blank">WordPress.org</a>', 'wp-user-manager' ),
		'https://wpusermanager.com',
		'http://wordpress.org/support/view/plugin-reviews/wp-user-manager?filter=5#postform'
	);
	return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . ' <span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span></span>';
}
add_filter( 'admin_footer_text', 'wpum_admin_rate_us' );

/**
 * Add new links to the plugin's action links list.
 *
 * @since 1.0.0
 * @return array
 */
function wpum_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'users.php?page=wpum-settings' ) . '">' . __( 'Settings','wp-user-manager' ) . '</a>';
	$docs_link     = '<a href="https://docs.wpusermanager.com/" target="_blank">' . __( 'Documentation','wp-user-manager' ) . '</a>';
	$addons_link   = '<a href="http://wpusermanager.com/addons" target="_blank">' . __( 'Addons','wp-user-manager' ) . '</a>';
	array_unshift( $links, $settings_link );
	array_unshift( $links, $docs_link );
	array_unshift( $links, $addons_link );
	return $links;
}
add_filter( 'plugin_action_links_' . WPUM_SLUG, 'wpum_add_settings_link' );

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
 * Filter the wp_login_url function by using the built-in wpum page.
 *
 * @param string $login_url
 * @param string $redirect
 * @param boolean $force_reauth
 * @return void
 */
function wpum_login_url( $login_url, $redirect, $force_reauth ) {

	$wpum_login_page = wpum_get_core_page_id( 'login' );
	$wpum_login_page = get_permalink( $wpum_login_page );

	if( $redirect ) {
		$wpum_login_page = add_query_arg( [ 'redirect_to' => $redirect ], $wpum_login_page );
	}

	return $wpum_login_page;

}
add_filter( 'login_url', 'wpum_login_url', 10, 3 );

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
			return new WP_Error( 'username_only', __( 'Invalid username or incorrect password.', 'wp-user-manager' ) );
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

			return new WP_Error( 'email_only', __( 'Invalid email address or incorrect password.', 'wp-user-manager' ) );

		}

	}

	return $user;

}
add_filter( 'authenticate', 'wpum_authentication', 20, 3 );

/**
 * Highlight all pages used by WPUM.
 *
 * @param array $post_states
 * @param object $post
 * @return void
 */
function wpum_highlight_pages( $post_states, $post ) {

	$mark    = '<img style="width:13px;" src="'. WPUM_PLUGIN_URL .'/assets/images/logo.svg" title="WP User Manager Page">';
	$post_id = $post->ID;

	switch ( $post_id ) {
		case wpum_get_core_page_id( 'login' ):
		case wpum_get_core_page_id( 'register' ):
		case wpum_get_core_page_id( 'password' ):
		case wpum_get_core_page_id( 'account' ):
		case wpum_get_core_page_id( 'profile' ):
		case wpum_get_core_page_id( 'registration-confirmation' ):
		case wpum_get_core_page_id( 'login-redirect' ):
		case wpum_get_core_page_id( 'logout-redirect' ):
			$post_states['wpum_page'] = $mark;
			break;
	}

	return $post_states;

}
add_filter( 'display_post_states', 'wpum_highlight_pages', 10, 2 );

/**
 * Filters the upload dir when $wpum_upload is true
 *
 * @param  array $pathdata
 * @return array
 */
function wpum_upload_dir( $pathdata ) {
	global $wpum_upload, $wpum_uploading_file;

	if ( ! empty( $wpum_upload ) ) {

		$dir = apply_filters( 'wpum_upload_dir', 'wp-user-manager-uploads' );

		if ( empty( $pathdata['subdir'] ) ) {
			$pathdata['path']   = $pathdata['path'] . '/' . $dir;
			$pathdata['url']    = $pathdata['url'] . '/' . $dir;
			$pathdata['subdir'] = '/' . $dir;
		} else {
			$new_subdir         = '/' . $dir . $pathdata['subdir'];
			$pathdata['path']   = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['path'] );
			$pathdata['url']    = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['url'] );
			$pathdata['subdir'] = str_replace( $pathdata['subdir'], $new_subdir, $pathdata['subdir'] );
		}

	}

	return $pathdata;
}
add_filter( 'upload_dir', 'wpum_upload_dir' );
