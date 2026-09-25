<?php
/**
 * Registers a US States field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2021, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a US States dropdown field type.
 *
 * The two letter USPS code is stored as the value, the full state name is displayed.
 */
class WPUM_Field_States extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->type              = 'states';
		$this->icon              = 'dashicons-location-alt';
		$this->group             = 'advanced';
		$this->allow_default     = false;
		$this->min_addon_version = '2.6.0';
	}

	/**
	 * Set the name of the field.
	 *
	 * @return void
	 */
	public function set_name() {
		$this->name = esc_html__( 'US States', 'wp-user-manager' );
	}

	/**
	 * Register the field and supply its options to the forms.
	 *
	 * @return void
	 */
	public function register() {
		parent::register();

		add_filter( 'wpum_form_custom_field_dropdown_options', array( $this, 'get_field_options' ), 10, 2 );
	}

	/**
	 * Populate the dropdown options for states fields.
	 *
	 * @param array       $options
	 * @param \WPUM_Field $field
	 *
	 * @return array
	 */
	public function get_field_options( $options, $field ) {
		if ( ! is_object( $field ) || ! method_exists( $field, 'get_type' ) || $this->type !== $field->get_type() ) {
			return $options;
		}

		$states = wpum_get_us_states();

		if ( $field->get_meta( 'allow_multiple' ) ) {
			return $states;
		}

		// Without an empty first option the browser preselects the first state, which would then be saved.
		$placeholder = $field->get_meta( 'placeholder' );
		$placeholder = $placeholder ? $placeholder : __( 'Select a state', 'wp-user-manager' );

		return array( '' => $placeholder ) + $states;
	}

	/**
	 * @return array
	 */
	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['general'] ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return array(
			'general' => array(
				'allow_multiple' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Allow multiple selection', 'wp-user-manager' ),
					'model'   => 'allow_multiple',
					'default' => false,
				),
			),
		);
	}

	/**
	 * Gets the value of a posted states field, discarding anything that is not a known state code.
	 *
	 * @param string $key
	 * @param array  $field
	 *
	 * @return array|string
	 */
	public function get_posted_field( $key, $field ) {
		if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore
			return '';
		}

		$states = wpum_get_us_states();
		$value  = $this->sanitize_posted_field( $_POST[ $key ] ); // phpcs:ignore

		if ( is_array( $value ) ) {
			return array_values( array_filter( $value, function ( $code ) use ( $states ) {
				return is_string( $code ) && isset( $states[ $code ] );
			} ) );
		}

		return isset( $states[ $value ] ) ? $value : '';
	}

	/**
	 * Format the output onto the profiles for the states field.
	 *
	 * @param object $field
	 * @param mixed  $value
	 *
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		$states = wpum_get_us_states();
		$names  = array();

		foreach ( (array) $value as $code ) {
			if ( is_scalar( $code ) && isset( $states[ $code ] ) ) {
				$names[] = $states[ $code ];
			}
		}

		return esc_html( implode( ', ', $names ) );
	}
}
