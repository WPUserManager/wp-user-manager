<?php
/**
 * Set of functions that deals with the fields of the plugin.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve a list of registered field types and their field type groups.
 *
 * @return array
 */
function wpum_get_registered_field_types() {

	$fields = array(
		'default' => [
			'group_name' => esc_html__( 'Default Fields' ),
			'fields'     => []
		],
		'standard' => [
			'group_name' => esc_html__( 'Standard Fields' ),
			'fields'     => []
		],
		'advanced' => [
			'group_name' => esc_html__( 'Advanced Fields' ),
			'fields'     => []
		],
	);

	$registered_fields = apply_filters( 'wpum_registered_field_types', $fields );

	return $registered_fields;

}

/**
 * Retrieve a list of the registered field types names.
 *
 * @return array
 */
function wpum_get_registered_field_types_names() {
	$registered_types = [];

	foreach( wpum_get_registered_field_types() as $status => $types ) {
		if( ! empty( $types['fields'] ) ) {
			foreach( $types['fields'] as $field_type ) {
				$registered_types[ $field_type['type'] ] = $field_type['name'];
			}
		}
	}

	return $registered_types;
}
