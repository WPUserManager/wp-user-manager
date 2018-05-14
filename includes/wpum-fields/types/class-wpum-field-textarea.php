<?php
/**
 * Registers a textarea field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a text field type.
 */
class WPUM_Field_Textarea extends WPUM_Field_Type {

	/**
	 * Hook into the main class and register a new field.
	 *
	 * @return void
	 */
	public function init() {

		// Define field type information.
		$this->name  = esc_html__( 'Textarea', 'wp-user-manager' );
		$this->type  = 'textarea';
		$this->icon  = 'dashicons-editor-paragraph';
		$this->order = 3;

	}

}

new WPUM_Field_Textarea();
