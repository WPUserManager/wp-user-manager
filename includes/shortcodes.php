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

function wpum_login_form( $atts, $content = null ) {

	extract( shortcode_atts( array(
		'login_link'     => '',
		'psw_link'       => '',
		'register_link'  => ''
	), $atts ) );

	ob_start();

	WPUM()->templates
		->set_template_data( [ 'psw' => $psw_link, 'register' => $register_link, 'login_label' => wpum_get_login_label() ] )
		->get_template_part( 'forms/form', 'login' );

	$output = ob_get_clean();

	return $output;

}
add_shortcode( 'wpum_login_form', 'wpum_login_form' );
