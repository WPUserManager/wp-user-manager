<?php
/**
 * Handles the editor control type registration with the customizer.
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
 * The class that handles the editor control inside the customizer.
 */
class WPUM_Emails_Customizer_Editor_Control extends WP_Customize_Control {

	/**
	 * Type of control, used by JS.
	 *
	 * @access public
	 * @var string
	 */
	public $type = 'email_editor';

	/**
	 * Enqueue scripts
	 */
	public function enqueue() {
		wp_enqueue_style( 'wpum-editor-control-style', WPUM_PLUGIN_URL . 'assets/css/admin/email-editor-control.css', false, WPUM_VERSION );
	}

	/**
	 * Render the content of this control.
	 *
	 * @access protected
	 */
	public function render_content() {

		?>

		<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
		<a href="#" class="button button-hero" id="wpum-email-editor-btn">
			<span class="dashicons dashicons-edit"></span>
			<span><?php esc_html_e( 'Open email content editor', 'wp-user-manager' ); ?></span>
		</a>

		<div id="wpum-editor-window" class="editor-window">
			<textarea name="wpum-mail-content-editor" id="wpum-mail-content-editor" cols="30" rows="10">
				<?php echo esc_textarea( $this->value() ); ?>
			</textarea>
		</div>

		<br/>

		<?php

	}

}
