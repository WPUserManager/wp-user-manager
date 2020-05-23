<?php
/**
 * Registers a number field for the forms.
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
class WPUM_Field_Number extends WPUM_Field_Type {

	public function __construct() {
		$this->name  = esc_html__( 'Number', 'wp-user-manager' );
		$this->type  = 'number';
		$this->icon  = 'dashicons-leftright';
		$this->order = 3;

	}

}

