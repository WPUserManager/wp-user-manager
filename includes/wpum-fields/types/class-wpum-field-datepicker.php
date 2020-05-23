<?php
/**
 * Registers a datepicker field for the forms.
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
 * Register a text field type.
 */
class WPUM_Field_Datepicker extends WPUM_Field_Type {

	public function __construct() {
		$this->group = 'advanced';
		$this->name  = esc_html__( 'Datepicker', 'wp-user-manager' );
		$this->type  = 'datepicker';
		$this->icon  = 'dashicons-calendar-alt';
		$this->order = 3;
	}
}
