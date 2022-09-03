<?php
/**
 * Registers a checkbox field for the forms.
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
 * Register a checkbox field type.
 */
class WPUM_Field_Checkbox extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name          = esc_html__( 'Single checkbox', 'wp-user-manager' );
		$this->type          = 'checkbox';
		$this->icon          = 'dashicons-yes';
		$this->order         = 3;
		$this->allow_default = true;
		$this->default_type  = 'checkbox';
	}

	/**
	 * Format the output onto the profiles for the checkbox field.
	 *
	 * @param object $field
	 * @param mixed  $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		return esc_html__( 'Yes', 'wp-user-manager' );
	}

}

