<?php
/**
 * Handles the email customizer scripts in the admin panel.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class that handles all the scripts of the email customizer.
 */
class WPUM_Emails_Customizer_Scripts {

	/**
	 * Store the registered emails of the plugin.
	 *
	 * @var array
	 */
	public $registered_emails;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->registered_emails = wpum_get_registered_emails();
		add_action( 'customize_preview_init', array( $this, 'customize_preview' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'customize_controls' ), 90 );
	}

	/**
	 * Scripts for the live preview.
	 *
	 * @return void
	 */
	public function customize_preview() {

		wp_enqueue_script( 'wpum-sanitize-html', WPUM_PLUGIN_URL . 'assets/js/vendor/sanitize-html.min.js', array( 'customize-preview' ), WPUM_VERSION, true );
		wp_enqueue_script( 'wpum-email-customize-preview', WPUM_PLUGIN_URL . 'assets/js/admin/admin-email-customizer-preview.min.js', array( 'customize-preview' ), WPUM_VERSION, true );

		$js_variables = array(
			'emails' => wpum_get_registered_emails(),
		);

		wp_localize_script( 'wpum-email-customize-preview', 'wpumCustomizePreview', $js_variables );

	}

	/**
	 * Scripts for the controls.
	 *
	 * @return void
	 */
	public function customize_controls() {
		$selected_email_id = filter_input( INPUT_GET, 'email', FILTER_SANITIZE_STRING );

		wp_enqueue_editor();
		wp_enqueue_script( 'wpum-email-customize-controls', WPUM_PLUGIN_URL . 'assets/js/admin/admin-email-customizer-controls.min.js', array( 'customize-controls' ), WPUM_VERSION, true );

		// Create a list of registered sections based on the registered emails.
		$sections = array();
		foreach ( $this->registered_emails as $email_id => $email_settings ) {
			$sections[] = $email_id . '_settings';
		}

		$js_variables = array(
			'labels'            => array(
				'open'            => esc_html__( 'Open email content editor', 'wp-user-manager' ),
				'close'           => esc_html__( 'Close email content editor', 'wp-user-manager' ),
				'addMerge'        => esc_html__( 'Add merge tags', 'wp-user-manager' ),
				'addMergeTooltip' => esc_html__( 'Merge tags allow you to dynamically add content to your email', 'wp-user-manager' ),
			),
			'email_content'     => wpum_get_email_field( $email_id, 'content' ),
			'selected_email_id' => $selected_email_id,
			'mergeTags'         => WPUM()->emails->get_tags(),
			'sections'          => $sections,
		);
		wp_localize_script( 'wpum-email-customize-controls', 'wpumCustomizeControls', $js_variables );

	}

}

new WPUM_Emails_Customizer_Scripts();
