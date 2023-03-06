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

	/**
	 * Construct
	 */
	public function __construct() {
		$this->group = 'advanced';
		$this->name  = esc_html__( 'Datepicker', 'wp-user-manager' );
		$this->type  = 'datepicker';
		$this->icon  = 'dashicons-calendar-alt';
		$this->order = 3;
	}

	/**
	 * @param object $field
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		return date_i18n( apply_filters( 'wpum_field_datepicker_date_format', get_option( 'date_format' ) ), strtotime( $value ) );
	}
}
