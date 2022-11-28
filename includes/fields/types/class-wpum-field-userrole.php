<?php
/**
 * Registers a User field for the forms.
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
 * Register a dropdown field type.
 */
class WPUM_Field_Userrole extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name              = esc_html__( 'User Role', 'wp-user-manager' );
		$this->type              = 'userrole';
		$this->icon              = 'dashicons-admin-generic';
		$this->group             = 'advanced';
		$this->min_addon_version = '2.5';
	}

	/**
	 * @return array
	 */
	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['general'] ) );
	}

	/**
	 * Gets the value of a posted multiselect field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return array
	 */
	public function get_posted_field( $key, $field ) {
		if ( isset( $_POST[ $key ] ) && is_array( $_POST[ $key ] ) ) { // phpcs:ignore
			return isset( $_POST[ $key ] ) ? array_map( 'sanitize_text_field', $_POST[ $key ] ) : array(); // phpcs:ignore
		}

		return filter_input( INPUT_POST, $key, FILTER_SANITIZE_STRING );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		$settings = array(
			'general' => array(
				'options'    => array(
					'type'     => 'multiselect',
					'label'    => __( 'User Roles', 'wp-user-manager' ),
					'hint'     => esc_html__( 'List of roles the users can select from', 'wp-user-manager' ),
					'model'    => 'options',
					'labels'   => array(),
					'required' => true,
					'options'  => wpum_get_roles(),
					'multiple' => true,
				),
				'type_label' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Type label', 'wp-user-manager' ),
					'model'     => 'type_label',
					'default'   => 'Role',
				),
			),
		);

		if ( wpum_get_option( 'allow_multiple_user_roles' ) ) {
			$settings['general']['allow_multiple'] = array(
				'type'    => 'checkbox',
				'label'   => esc_html__( 'Allow multiple selection', 'wp-user-manager' ),
				'hint'    => esc_html__( 'Allow users to select multiple roles for themselves', 'wp-user-manager' ),
				'model'   => 'allow_multiple',
				'default' => false,
			);
		}

		return $settings;
	}

	/**
	 * Format the output onto the profiles for the taxonomy field.
	 *
	 * @param object $field
	 * @param mixed  $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		global $wp_roles;
		$available_roles = $wp_roles->get_names();

		$selected_roles = array();
		foreach ( $value as $role ) {
			$selected_roles[] = $available_roles[ $role ];
		}

		return implode( ', ', $selected_roles );
	}
}
