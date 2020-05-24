<?php
/**
 * Registers an url field for the forms.
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
class WPUM_Field_Url extends WPUM_Field_Type {

	public function __construct() {
		$this->name  = esc_html__( 'URL', 'wp-user-manager' );
		$this->type  = 'url';
		$this->icon  = 'dashicons-admin-links';
		$this->order = 3;
	}

	/**
	 * Format the output onto the profiles for the url field.
	 *
	 * @param object $field
	 * @param mixed $value
	 * @return string
	 */
	function get_formatted_output( $field, $value ) {
		return '<a href="'. esc_url( $value ) .'" target="_blank" rel="noopener noreferrer">'. esc_html( $value ) .'</a>';
	}
}
