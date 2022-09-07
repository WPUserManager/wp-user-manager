<?php
/**
 * Registers a textarea field for the forms.
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
class WPUM_Field_Textarea extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name          = esc_html__( 'Textarea', 'wp-user-manager' );
		$this->type          = 'textarea';
		$this->icon          = 'dashicons-editor-paragraph';
		$this->order         = 3;
		$this->allow_default = true;
		$this->default_type  = 'textArea';
	}

	/**
	 * Gets the value of a posted textarea field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	public function get_posted_field( $key, $field ) {
		$field = filter_input( INPUT_POST, $key );

		return $field ? wp_kses_post( trim( wp_unslash( $field ) ) ) : '';
	}

	/**
	 * Format the output of the textarea field onto profile pages.
	 *
	 * @param object $field
	 * @param mixed  $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		return wpautop( wp_kses_post( $value ) );
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
