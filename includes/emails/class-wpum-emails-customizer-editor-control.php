<?php
/**
 * Handles the editor control type registration with the customizer.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
	 * Render the content of this control.
	 *
	 * @access protected
	 */
	public function render_content() {

		?>

		<label class="customize-control-title"><?php echo esc_html( $this->label ); ?></label>
		<div id="wpum-email-content-editor"></div>
		<br/>
		<strong><?php esc_html_e( 'Available email tags:' ); ?></strong><br/>
		<?php echo wpum_get_emails_tags_list(); ?>

		<?php

	}

}
