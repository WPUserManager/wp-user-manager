<?php
/**
 * Registers a text field for the forms.
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
class WPUM_Field_Email extends WPUM_Field_Type {

	/**
	 * Hook into the main class and register a new field.
	 *
	 * @return void
	 */
	public function init() {

		// Define field type information.
		$this->name  = esc_html__( 'Email' );
		$this->type  = 'email';
		$this->icon  = 'dashicons-email-alt';
		$this->order = 3;

	}

	/**
	 * Add field specific settings for this type.
	 *
	 * @return void
	 */
	public function get_editor_settings() {

		return array(
			'validation' => [
				$this->add_requirement_setting()
			],
			'privacy' => [
				$this->add_visibility_setting()
			],
			'permissions' => [
				$this->add_editing_permissions_setting(),
				$this->add_read_only_setting()
			]
		 );

	}

}

new WPUM_Field_Email();
