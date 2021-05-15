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
class WPUM_Field_User extends WPUM_Field_Type {

	public function __construct() {
		$this->name  = esc_html__( 'User', 'wp-user-manager' );
		$this->type  = 'user';
		$this->icon  = 'dashicons-admin-users';
		$this->group = 'advanced';
		$this->allow_default = false;
		$this->min_addon_version = '2.3';
	}

	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['general'] ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		$roles = array_map( function ( $role ) {
			$role['id']   = $role['value'];
			$role['name'] = $role['label'];
			unset( $role['value'] );
			unset( $role['label'] );

			return $role;
		}, wpum_get_roles() );

		return [
			'general' => [
				'role'   => array(
					'type'   => 'select',
					'label'  => esc_html__( 'Filter by role', 'wp-user-manager' ),
					'model'  => 'role',
					'required'  => true,
					'values' => array_merge( array(array(
						'id'   => '',
						'name' => 'All Roles',
					)), $roles ),
				),
				'allow_multiple' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Allow multiple selection', 'wp-user-manager' ),
					'model'   => 'allow_multiple',
					'default' => false,
				),
				'show_hidden' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Show users with hidden profiles', 'wp-user-manager' ),
					'model'   => 'show_hidden',
					'default' => false,
				),
				'type_label' => array(
					'type'        => 'input',
					'inputType'   => 'text',
					'label'   => esc_html__( 'Type label', 'wp-user-manager' ),
					'model'   => 'type_label',
					'default' => 'User',
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

		$users = get_users( array( 'include' => $value ) );

		return implode( ', ', wp_list_pluck( $users, apply_filters( 'wpum_user_field_type_value_key', 'display_name' ) ) );
	}
}
