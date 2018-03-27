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

	$registered_fields = apply_filters( 'wpum_fields_registered_field_types', $fields );

	return $registered_fields;

}
