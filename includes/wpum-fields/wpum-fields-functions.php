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
				'name' => esc_html__( 'Primary fields', 'wp-user-manager' )
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
 * Install the cover image field into the database.
 *
 * @return void
 */
function wpum_install_cover_image_field() {

	$group_id = 1;

	$field = array(
		'group_id'   => $group_id,
		'type'       => 'user_cover',
		'name'       => 'Profile cover image',
	);

	$save_field = new WPUM_Field();
	$save_field->add( $field );
	$save_field->add_meta( 'user_meta_key', 'user_cover' );
	$save_field->add_meta( 'editing', 'public' );
	$save_field->add_meta( 'visibility', 'public' );

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
		'user_avatar',
		'user_cover'
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
			'name' => esc_html__( 'General', 'wp-user-manager' )
		),
		array(
			'id'   => 'validation',
			'name' => esc_html__( 'Validation', 'wp-user-manager' )
		),
		array(
			'id'   => 'privacy',
			'name' => esc_html__( 'Privacy', 'wp-user-manager' )
		),
		array(
			'id'   => 'permissions',
			'name' => esc_html__( 'Permissions', 'wp-user-manager' )
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

/**
 * Use this function to start a loop of profile fields.
 *
 * @global $wpum_profile_fields
 * @param  array  $args arguments to create the loop.
 * @return boolean       whether there's any group found.
 */
function wpum_has_profile_fields( $args = [] ) {

	global $wpum_profile_fields;

	$wpum_profile_fields = new WPUM_Fields_Query( $args );

	return apply_filters( 'wpum_has_profile_fields', $wpum_profile_fields->has_groups(), $wpum_profile_fields );
}

/**
 * Setup the profile fields loop.
 *
 * @global $wpum_profile_fields
 * @return bool
 */
function wpum_profile_field_groups() {
	global $wpum_profile_fields;
	return $wpum_profile_fields->profile_groups();
}

/**
 * Setup the current field group within the loop.
 *
 * @global $wpum_profile_fields
 * @return array the current group within the loop.
 */
function wpum_the_profile_field_group() {
	global $wpum_profile_fields;
	return $wpum_profile_fields->the_profile_group();
}

/**
 * Return the group id number of a group within the loop.
 *
 * @global $wpum_fields_group
 * @return string the current group id.
 */
function wpum_get_field_group_id() {
	global $wpum_fields_group;
	return apply_filters( 'wpum_get_field_group_id', $wpum_fields_group->get_ID() );
}

/**
 * Echo the group id number of a group within the loop.
 *
 * @return void
 */
function wpum_the_field_group_id() {
	echo wpum_get_field_group_id();
}

/**
 * Return the name of a group within the loop.
 *
 * @global $wpum_fields_group
 * @return string
 */
function wpum_get_field_group_name() {
	global $wpum_fields_group;
	return apply_filters( 'wpum_get_field_group_name', $wpum_fields_group->get_name() );
}

/**
 * Echo the name of a group within the loop.
 *
 * @return void
 */
function wpum_the_field_group_name() {
	echo wpum_get_field_group_name();
}

/**
 * Return the slug of a group within the loop.
 *
 * @global $wpum_fields_group
 * @return string
 */
function wpum_get_field_group_slug() {
	global $wpum_fields_group;
	return apply_filters( 'wpum_get_field_group_slug', sanitize_title( $wpum_fields_group->get_name() ) );
}

/**
 * Echo the slug of a group within the loop.
 *
 * @return void
 */
function wpum_the_field_group_slug() {
	echo wpum_get_field_group_slug();
}

/**
 * Retrieve the description of the group within the loop.
 *
 * @global $wpum_fields_group
 * @return string
 */
function wpum_get_field_group_description() {
	global $wpum_fields_group;
	return apply_filters( 'wpum_get_field_group_description', $wpum_fields_group->get_description() );
}

/**
 * Echo the description of a field group within the loop.
 *
 * @return void
 */
function wpum_the_field_group_description() {
	echo wpum_get_field_group_description();
}

/**
 * Whether the current group within the loop has fields.
 *
 * @global $wpum_profile_fields
 * @return array the current group fields within the loop.
 */
function wpum_field_group_has_fields() {
	global $wpum_profile_fields;
	return $wpum_profile_fields->has_fields();
}

/**
 * Start the fields loop for the current group.
 *
 * @global $wpum_profile_fields
 * @return mixed
 */
function wpum_profile_fields() {
	global $wpum_profile_fields;
	return $wpum_profile_fields->profile_fields();
}

/**
 * Setup global variable for field within the loop.
 *
 * @global $wpum_profile_fields
 * @return void
 */
function wpum_the_profile_field() {
	global $wpum_profile_fields;
	return $wpum_profile_fields->the_profile_field();
}

/**
 * Retrieve the current field id within the loop.
 *
 * @global $wpum_field
 * @return int field id
 */
function wpum_get_field_id() {
	global $wpum_field;
	return $wpum_field->get_ID();
}

/**
 * Echo the current field id within a loop.
 *
 * @see wpum_get_the_field_id()
 * @return void
 */
function wpum_the_field_id() {
	echo (int) wpum_get_field_id();
}

/**
 * Retrieve the current field name within the loop.
 *
 * @global $wpum_field
 * @return string field name
 */
function wpum_get_field_name() {
	global $wpum_field;
	return apply_filters( 'wpum_get_field_name', $wpum_field->get_name(), $wpum_field->get_ID() );
}

/**
 * Echo the current field name within a loop.
 *
 * @see wpum_get_field_name()
 * @return void
 */
function wpum_the_field_name() {
	echo wpum_get_field_name();
}

/**
 * Retrieve the current field description within a loop.
 *
 * @global $wpum_field
 * @return string description of the field.
 */
function wpum_get_field_description() {
	global $wpum_field;
	return apply_filters( 'wpum_get_field_description', $wpum_field->get_description(), $wpum_field->get_ID() );
}

/**
 * Echo the current field description within a loop.
 *
 * @see wpum_get_field_description()
 * @return void
 */
function wpum_the_field_description() {
	echo wpum_get_field_description();
}

/**
 * Verify whether the current field within the loop is required.
 *
 * @global $wpum_field
 * @return bool
 */
function wpum_is_field_required() {
	global $wpum_field;
	return apply_filters( 'wpum_is_field_required', $wpum_field->is_required(), $wpum_field->get_ID() );
}

/**
 * Retrieve the current field type within a loop.
 *
 * @global $wpum_field
 * @return string the type of the field.
 */
function wpum_get_field_type() {
	global $wpum_field;
	return $wpum_field->get_type();
}

/**
 * Retrieve the classes for the field element as an array.
 *
 * @param  string $class custom class to add to the field.
 * @return array        list of all classes.
 */
function wpum_get_field_css_class( $class = false ) {

	global $wpum_profile_fields;

	$classes = array();

	if ( ! empty( $class ) ) {
		$classes[] = sanitize_title( esc_attr( $class ) );
	}

	// Add a class with the field id.
	$classes[] = 'field_' . $wpum_profile_fields->field->get_ID();
	// Add a class with the field name.
	$classes[] = 'field_' . sanitize_title( $wpum_profile_fields->field->get_name() );
	// Add a class with the field type.
	$classes[] = 'field_type_' . sanitize_title( $wpum_profile_fields->field->get_type() );

	// Sanitize all classes.
	$classes = array_map( 'esc_attr', $classes );

	return apply_filters( 'wpum_field_css_class', $classes, $class );

}

/**
 * Display the css classes applied to a field.
 *
 * @param  string $class custom class to add to the fields.
 * @return void
 */
function wpum_the_field_css_class( $class = false ) {
	echo join( ' ', wpum_get_field_css_class( $class ) );
}

/**
 * Verify if a field has user data.
 *
 * @return boolean
 */
function wpum_field_has_data() {
	global $wpum_profile_fields;
	return $wpum_profile_fields->field_has_data;
}

/**
 * Retrieve the value of a field within the loop.
 *
 * @return mixed
 */
function wpum_get_the_field_value() {

	global $wpum_field;

	return apply_filters( 'wpum_get_the_field_value', $wpum_field->get_value(), $wpum_field );
}

/**
 * Output the value of a field within the loop.
 *
 * @return void
 */
function wpum_the_field_value() {
	echo wpum_get_the_field_value();
}

/**
 * Wrapper function for size_format - checks the max size of the avatar and cover field.
 *
 * @param array   $field
 * @param string  $size  in bytes
 * @return string
 */
function wpum_max_upload_size( $field_name = '', $custom_size = false ) {

	// Default max upload size,
	$output = size_format( wp_max_upload_size() );

	// Check if the field is the avatar upload field and max size is defined,
	if ( $field_name == 'user_avatar' && defined( 'WPUM_MAX_AVATAR_SIZE' ) ) {
		$output = size_format( WPUM_MAX_AVATAR_SIZE );
	} else if( $field_name == 'user_cover' && defined( 'WPUM_MAX_COVER_SIZE' ) ) {
		$output = size_format( WPUM_MAX_COVER_SIZE );
	}

	if ( $custom_size ) {
		$output = size_format( intval( $custom_size ), 0 );
	}

	return $output;
}
