<?php
/**
 * Functions collection to work with fields.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Installs the default fields group into the WordPress database.
 *
 * @return void
 */
function wpum_install_default_field_group() {

	if( ! get_option( 'wpum_version_upgraded_from' ) ) {
		$default_group = new WPUM_Field_Group();
		$default_group->add(
			[
				'id'   => 1,
				'name' => esc_html__( 'Primary fields' )
			]
		);
	}

}

/**
 * Install the default primary fields within the default group.
 *
 * @return void
 */
function wpum_install_fields() {

	if( ! get_option( 'wpum_version_upgraded_from' ) ) {

		$group_id = 1;

		$fields = [
			array(
				'id'         => 1,
				'group_id'   => $group_id,
				'type'       => 'username',
				'name'       => 'Username',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'user_meta_key' => 'username'
				]
			),
			array(
				'id'         => 2,
				'group_id'   => $group_id,
				'type'       => 'user_email',
				'name'       => 'Email',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'user_email'
				]
			),
			array(
				'id'         => 3,
				'group_id'   => $group_id,
				'type'       => 'user_password',
				'name'       => 'Password',
				'metas'      => [
					'required'      => true,
					'user_meta_key' => 'user_password'
				]
			),
			array(
				'id'         => 4,
				'group_id'   => $group_id,
				'type'       => 'user_firstname',
				'name'       => 'First name',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'firstname'
				]
			),
			array(
				'id'         => 5,
				'group_id'   => $group_id,
				'type'       => 'user_lastname',
				'name'       => 'Last name',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'lastname'
				]
			),
			array(
				'id'         => 6,
				'group_id'   => $group_id,
				'type'       => 'user_nickname',
				'name'       => 'Nickname',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'nickname'
				]
			),
			array(
				'id'         => 7,
				'group_id'   => $group_id,
				'type'       => 'user_displayname',
				'name'       => 'Display name',
				'metas'      => [
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'display_name'
				]
			),
			array(
				'id'         => 8,
				'group_id'   => $group_id,
				'type'       => 'user_website',
				'name'       => 'Website',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'user_url'
				]
			),
			array(
				'id'         => 9,
				'group_id'   => $group_id,
				'type'       => 'user_description',
				'name'       => 'Description',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'description'
				]
			),
			array(
				'id'         => 10,
				'group_id'   => $group_id,
				'type'       => 'user_avatar',
				'name'       => 'Profile picture',
				'metas'      => [
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'current_user_avatar'
				]
			)
		];

		$order = 0;

		foreach( $fields as $field ) {

			$order++;
			$field['field_order'] = $order;

			$save_field = new WPUM_Field();
			$save_field->add( $field );

			foreach( $field['metas'] as $meta_key => $meta_value ) {
				$save_field->add_meta( $meta_key, $meta_value );
			}

		}

	}

}

/**
 * An array of primary field types.
 *
 * @return array
 */
function wpum_get_primary_field_types() {

	$types = [
		'username',
		'user_email',
		'user_password',
		'user_firstname',
		'user_lastname',
		'user_nickname',
		'user_displayname',
		'user_website',
		'user_description',
		'user_avatar'
	];

	return apply_filters( 'wpum_get_primary_field_types', $types );

}

/**
 * Setup the tabs for the edit field dialog in the admin panel.
 *
 * @return array
 */
function wpum_get_edit_field_dialog_tabs() {

	$tabs = [
		array(
			'id'   => 'general',
			'name' => esc_html__( 'General' )
		),
		array(
			'id'   => 'validation',
			'name' => esc_html__( 'Validation' )
		),
		array(
			'id'   => 'privacy',
			'name' => esc_html__( 'Privacy' )
		),
		array(
			'id'   => 'permissions',
			'name' => esc_html__( 'Permissions' )
		),
	];

	return apply_filters( 'wpum_get_fields_editor_edit_tabs', $tabs );

}

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

function wpum_get_fields_groups( $args ) {

}
