<?php
/**
 * Registers a dropdown field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a dropdown field type.
 */
class WPUM_Field_Dropdown extends WPUM_Field_Type {

	/**
	 * Hook into the main class and register a new field.
	 *
	 * @return void
	 */
	public function init() {

		// Define field type information.
		$this->name  = esc_html__( 'Dropdown', 'wp-user-manager' );
		$this->type  = 'dropdown';
		$this->icon  = 'dashicons-editor-ul';
		$this->order = 3;

	}

}

new WPUM_Field_Dropdown();
