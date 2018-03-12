<?php
/**
 * Handles the email customizer scripts in the admin panel.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Emails_Customizer_Scripts {

	public function __construct() {
		add_action( 'customize_preview_init', array( $this, 'customize_preview' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls' ) );
	}

	public function customize_preview() {

		wp_enqueue_script( 'wpum-email-customize-preview', WPUM_PLUGIN_URL . 'assets/js/admin/admin-email-customizer-preview.min.js', array( 'customize-preview' ), WPUM_VERSION, true );

	}

	public function customize_controls() {

		wp_enqueue_script( 'wpum-email-customize-controls', WPUM_PLUGIN_URL . 'assets/js/admin/admin-email-customizer-controls.min.js', array( 'customize-controls' ), WPUM_VERSION, true );

	}

}

//new WPUM_Emails_Customizer_Scripts;
