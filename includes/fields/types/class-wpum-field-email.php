<?php
/**
 * Registers an email field for the forms.
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
class WPUM_Field_Email extends WPUM_Field_Type {

	/**
	 * Contruct
	 */
	public function __construct() {
		$this->name  = esc_html__( 'Email', 'wp-user-manager' );
		$this->type  = 'email';
		$this->icon  = 'dashicons-email-alt';
		$this->order = 3;
	}

	/**
	 * Format the output onto the profiles for the email field.
	 *
	 * @param object $field
	 * @param mixed  $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		return esc_html( antispambot( $value ) );
	}

	/**
	 * @return array
	 */
	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['validation'] ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return array(
			'validation' => array(
				'maxlength' => array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Character Limit', 'wp-user-manager' ),
					'model'     => 'maxlength',
					'hint'      => esc_html__( 'Leave blank for no limit.', 'wp-user-manager' ),
				),
			),
		);
	}
}
