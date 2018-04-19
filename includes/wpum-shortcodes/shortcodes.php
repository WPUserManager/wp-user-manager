<?php
/**
 * Register all the shortcodes for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Login form shortcode.
 * Vuejs handles the display of the form.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_login_form( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'psw_link'       => '',
		'register_link'  => ''
	), $atts ) );

	ob_start();

	if( is_user_logged_in() ) {
		WPUM()->templates
			->get_template_part( 'already-logged-in' );
	} else {
		echo WPUM()->forms->get_form( 'login', $atts );

		WPUM()->templates
			->set_template_data( $atts )
			->get_template_part( 'action-links' );
	}

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_login_form', 'wpum_login_form' );

/**
 * Password recovery shortcode.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_password_recovery( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'login_link'    => '',
		'register_link' => ''
	), $atts ) );

	ob_start();

	$output = ob_get_clean();

	if( is_user_logged_in() ) {
		WPUM()->templates
			->get_template_part( 'already-logged-in' );
	} else {
		echo WPUM()->forms->get_form( 'password-recovery', $atts );
	}

	return $output;

}
add_shortcode( 'wpum_password_recovery', 'wpum_password_recovery' );

/**
 * Display a login link.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_login_link( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'redirect' => '',
		'label'    => esc_html__( 'Login', 'wpum' )
	), $atts ) );

	if( is_user_logged_in() ) {
		$output = '';
	} else {
		$url    = wp_login_url( $redirect );
		$output = '<a href="'. esc_url( $url ) .'" class="wpum-login-link">'.esc_html( $label ).'</a>';
	}

	return $output;

}
add_shortcode( 'wpum_login', 'wpum_login_link' );

/**
 * Display a logout link.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_logout_link( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'redirect' => '',
		'label'    => esc_html__( 'Logout' )
	), $atts ) );

	$output = '';

	if( is_user_logged_in() ) {
		$output = '<a href="' . esc_url( wp_logout_url( $redirect ) ) . '">' . esc_html( $label ) . '</a>';
	}

	return $output;

}
add_shortcode( 'wpum_logout', 'wpum_logout_link' );

/**
 * Show the registration form through a shortcode.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_registration_form( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'login_link' => '',
		'psw_link'   => ''
	), $atts ) );

	$is_success = isset( $_GET['registration'] ) && $_GET['registration'] == 'success' ? true : false;

	ob_start();

	$output = ob_get_clean();

	if( wpum_is_registration_enabled() ) {

		if( is_user_logged_in() && ! $is_success ) {

			WPUM()->templates
				->get_template_part( 'already-logged-in' );

		} else if( $is_success ) {

			$success_message = apply_filters( 'wpum_registration_success_message', esc_html__( 'Registration complete. We have sent you a confirmation email with your details.' ) );

			WPUM()->templates
				->set_template_data( [
					'message' => $success_message
				] )
				->get_template_part( 'messages/general', 'success' );

		} else {

			echo WPUM()->forms->get_form( 'registration', $atts );

		}

	} else {

		WPUM()->templates
			->set_template_data( [
				'message' => esc_html__( 'Registrations are currently disabled.' )
			] )
			->get_template_part( 'messages/general', 'error' );

	}

	return $output;

}
add_shortcode( 'wpum_register', 'wpum_registration_form' );

/**
 * Display the account page of the user.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_account_page( $atts, $content = null ) {

	ob_start();

	$output = ob_get_clean();

	WPUM()->templates
		->set_template_data( [] )
		->get_template_part( 'account' );

	return $output;

}
add_shortcode( 'wpum_account', 'wpum_account_page' );

/**
 * Handles display of the profile shortcode.
 *
 * @param array $atts
 * @param string $content
 * @return void
 */
function wpum_profile( $atts, $content = null ) {

	ob_start();

	$output = ob_get_clean();

	WPUM()->templates
		->set_template_data( [] )
		->get_template_part( 'account' );

	return $output;

}
add_shortcode( 'wpum_profile', 'wpum_profile' );
