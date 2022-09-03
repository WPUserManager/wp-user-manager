<?php
/**
 * Registers a dropdown field for the forms.
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
 * Register a dropdown field type.
 */
class WPUM_Field_Dropdown extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name          = esc_html__( 'Dropdown', 'wp-user-manager' );
		$this->type          = 'dropdown';
		$this->icon          = 'dashicons-editor-ul';
		$this->order         = 3;
		$this->allow_default = true;
	}


	/**
	 * Format the output onto the profiles for the checkbox field.
	 *
	 * @param object $field
	 * @param mixed  $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		if ( ! $field->is_primary() ) {
			$options = $field->get_meta( 'dropdown_options' );
			if ( is_array( $options ) ) {
				foreach ( $options as $key => $option ) {
					if ( $option['value'] === $value ) {
						$value = $option['label'];
					}
				}
			}
		}

		return $value;
	}

}
