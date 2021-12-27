<?php
/**
 * Registers a User field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2021, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a dropdown field type.
 */
class WPUM_Field_Userrole extends WPUM_Field_Type {

	public function __construct() {
		$this->name  = esc_html__( 'User Role', 'wp-user-manager' );
		$this->type  = 'userrole';
		$this->icon  = 'dashicons-admin-generic';
		$this->group = 'advanced';
		$this->allow_default = true;
		$this->min_addon_version = '2.5';
	}

	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['general'] ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return [
			'general' => [
				'options'   => array(
					'type' => 'multiselect',
					'label'     => __( 'User Roles', 'wp-user-manager' ),
					'model'   => 'options',
					'labels' => [],
					'required' => true,
					'values'  => wpum_get_roles()
				),
				'allow_multiple' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Allow multiple selection', 'wp-user-manager' ),
					'model'   => 'allow_multiple',
					'default' => false,
				),
				'type_label' => array(
					'type'        => 'input',
					'inputType'   => 'text',
					'label'   => esc_html__( 'Type label', 'wp-user-manager' ),
					'model'   => 'type_label',
					'default' => 'Role',
				),
			],
		];
	}

	/**
	 * Format the output onto the profiles for the taxonomy field.
	 *
	 * @param object $field
	 * @param mixed $value
	 * @return string
	 */
	function get_formatted_output( $field, $value ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		return implode( ', ', $value );
	}

	public function template() {
		return $this->type;
	}
}
