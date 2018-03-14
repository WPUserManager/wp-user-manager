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

/**
 * The class that handles all the scripts of the email customizer.
 */
class WPUM_Emails_Customizer_Scripts {

	/**
	 * Get things started.
	 */
	public function __construct() {
		add_action( 'customize_preview_init', array( $this, 'customize_preview' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls' ), 90 );
	}

	/**
	 * Scripts for the live preview.
	 *
	 * @return void
	 */
	public function customize_preview() {

		wp_enqueue_script( 'wpum-sanitize-html', WPUM_PLUGIN_URL . 'assets/js/vendor/sanitize-html.min.js' , array( 'customize-preview' ), WPUM_VERSION, true );
		wp_enqueue_script( 'wpum-email-customize-preview', WPUM_PLUGIN_URL . 'assets/js/admin/admin-email-customizer-preview.min.js', array( 'customize-preview' ), WPUM_VERSION, true );

		$js_variables = [
			'emails' => wpum_get_registered_emails()
		];

		wp_localize_script( 'wpum-email-customize-preview', 'wpumCustomizePreview', $js_variables );

	}

	/**
	 * Scripts for the controls.
	 *
	 * @return void
	 */
	public function customize_controls() {

		$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

		if( $is_vue_dev ) {
			wp_register_script( 'wpum-email-customizer-editor-control', 'http://localhost:8080/EmailContentEditor.js', array( 'customize-controls' ), WPUM_VERSION, true );
		} else {
			wp_die('Yo VUEJS build missing');
		}
		wp_enqueue_editor();
		wp_enqueue_script( 'wpum-email-customizer-editor-control' );

		$js_variables = [
			'selected_email_id' => isset( $_GET['email'] ) ? esc_html( $_GET['email'] ) : false
		];
		wp_localize_script( 'wpum-email-customizer-editor-control', 'wpumCustomizeControls', $js_variables );

		/*
		wp_enqueue_script( 'wpum-email-customize-controls', WPUM_PLUGIN_URL . 'assets/js/admin/admin-email-customizer-controls.min.js', array( 'customize-controls' ), WPUM_VERSION, true );
		$js_variables = [
			'selected_email_id' => isset( $_GET['email'] ) ? esc_html( $_GET['email'] ) : false
		];
		wp_localize_script( 'wpum-email-customize-controls', 'wpumCustomizeControls', $js_variables );*/

	}

}

new WPUM_Emails_Customizer_Scripts;
