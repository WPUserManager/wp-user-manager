<?php
/**
 * Handles loading of all field types.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registration of the Fields loader class.
 */
class WPUM_Fields {

	protected $fields;

	protected $field_type_names;

	/**
	 * Load files and hook into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		// Parent class template.
		require_once WPUM_PLUGIN_DIR . 'includes/abstracts/abstract-wpum-field-type.php';

		// Now load all registered field types.
		$this->load();


	}

	/**
	 * Load registered field types classes.
	 *
	 * @return void
	 */
	public function load() {

		$fields = apply_filters( 'wpum_load_fields', [
			'text',
			'email',
			'password',
			'dropdown',
			'url',
			'textarea',
			'file',
			'checkbox',
			'multicheckbox',
			'multiselect',
			'radio',
			'number',
			'datepicker',
			'telephone',
			'video',
			'audio',
			'wysiwyg',
			'repeater',
			'hidden',
			'taxonomy',
			'user',
			'userrole',
		] );

		foreach ( $fields as $field ) {
			if ( file_exists( WPUM_PLUGIN_DIR . 'includes/wpum-fields/types/class-wpum-field-' . $field . '.php' ) ) {
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/types/class-wpum-field-' . $field . '.php';
			}

			$class = 'WPUM_Field_' . ucfirst( $field );
			if ( class_exists( $class ) ) {
				( new $class )->register();
			}
		}

	}

	/**
	 * Retrieve a list of registered field types and their field type groups.
	 *
	 * @return array
	 */
	function get_registered_field_types() {
		if ( $this->fields ) {
			return $this->fields;
		}

		$fields = array(
			'default' => [
				'group_name' => esc_html__( 'Default Fields', 'wp-user-manager' ),
				'fields'     => []
			],
			'standard' => [
				'group_name' => esc_html__( 'Standard Fields', 'wp-user-manager' ),
				'fields'     => []
			],
			'advanced' => [
				'group_name' => esc_html__( 'Advanced Fields', 'wp-user-manager' ),
				'fields'     => []
			],
		);

		$this->fields = apply_filters( 'wpum_registered_field_types', $fields );

		return $this->fields;
	}

	public function set_registered_field_types( $fields ) {
		$this->fields = $fields;
	}

	/**
	 * Retrieve a list of the registered field types names.
	 *
	 * @return array
	 */
	function get_registered_field_types_names() {
		if ( $this->field_type_names ) {
			return $this->field_type_names;
		}

		$registered_types = [];

		foreach( $this->get_registered_field_types() as $status => $types ) {
			if( ! empty( $types['fields'] ) ) {
				foreach( $types['fields'] as $field_type ) {
					$registered_types[ $field_type['type'] ] = $field_type['name'];
				}
			}
		}

		$this->field_type_names = $registered_types;

		return $this->field_type_names;
	}

}
