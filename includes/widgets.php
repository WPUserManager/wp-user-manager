<?php
/**
 * Register all the widgets for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register all widgets for WPUM.
 *
 * @return void
 */
function wpum_register_custom_widgets() {
	register_widget( 'WPUM_Login_Form_Widget' );
	register_widget( 'WPUM_Password_Recovery' );
	register_widget( 'WPUM_Recently_Registered_Users' );
	register_widget( 'WPUM_Registration_Form_Widget' );
}

add_action( 'widgets_init', 'wpum_register_custom_widgets', 1 );
