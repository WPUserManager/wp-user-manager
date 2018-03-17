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
