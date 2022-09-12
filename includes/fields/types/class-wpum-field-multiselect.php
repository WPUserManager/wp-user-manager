<?php
/**
 * Registers a multi select dropdown field for the forms.
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
class WPUM_Field_Multiselect extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name  = esc_html__( 'Multi Select', 'wp-user-manager' );
		$this->type  = 'multiselect';
		$this->icon  = 'dashicons-editor-alignleft';
		$this->order = 3;
	}

	/**
	 * Gets the value of a posted multiselect field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return array
	 */
	public function get_posted_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? array_map( 'sanitize_text_field', $_POST[ $key ] ) : array(); // phpcs:ignore
	}

	/**
	 * Determine output of multiselect field onto profile page.
	 *
	 * @param object $field
	 * @param array  $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {

		$stored_field_options = $field->get_meta( 'dropdown_options' );
		$stored_options       = array();
		$found_options_labels = array();

		foreach ( $stored_field_options as $key => $stored_option ) {
			$stored_options[ $stored_option['value'] ] = $stored_option['label'];
		}

		$values = array();

		foreach ( $value as $user_stored_value ) {
			$values[] = $stored_options[ $user_stored_value ];
		}

		return implode( ', ', $values );

	}

}
