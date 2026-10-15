<?php
/**
 * Functions collection to work with fields.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installs the default fields group into the WordPress database.
 *
 * @return void
 */
function wpum_install_default_field_group() {
	if ( ! get_option( 'wpum_version_upgraded_from' ) ) {
		$default_group = new WPUM_Field_Group();
		$default_group->add( array(
			'id'         => 1,
			'name'       => esc_html__( 'Primary fields', 'wp-user-manager' ),
			'is_primary' => 1,
		) );
	}
}

/**
 * Install the default primary fields within the default group.
 *
 * @return array
 */
function wpum_install_fields() {
	$saved_fields = array();
	if ( ! get_option( 'wpum_version_upgraded_from' ) ) {

		$group_id = 1;

		$fields = array(
			array(
				'id'       => 1,
				'group_id' => $group_id,
				'type'     => 'username',
				'name'     => 'Username',
				'metas'    => array(
					'required'      => true,
					'visibility'    => 'public',
					'user_meta_key' => 'username',
				),
			),
			array(
				'id'       => 2,
				'group_id' => $group_id,
				'type'     => 'user_email',
				'name'     => 'Email',
				'metas'    => array(
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'user_email',
				),
			),
			array(
				'id'       => 3,
				'group_id' => $group_id,
				'type'     => 'user_password',
				'name'     => 'Password',
				'metas'    => array(
					'required'      => true,
					'user_meta_key' => 'user_password',
				),
			),
			array(
				'id'       => 4,
				'group_id' => $group_id,
				'type'     => 'user_firstname',
				'name'     => 'First name',
				'metas'    => array(
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'firstname',
				),
			),
			array(
				'id'       => 5,
				'group_id' => $group_id,
				'type'     => 'user_lastname',
				'name'     => 'Last name',
				'metas'    => array(
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'lastname',
				),
			),
			array(
				'id'       => 6,
				'group_id' => $group_id,
				'type'     => 'user_nickname',
				'name'     => 'Nickname',
				'metas'    => array(
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'nickname',
				),
			),
			array(
				'id'       => 7,
				'group_id' => $group_id,
				'type'     => 'user_displayname',
				'name'     => 'Display name',
				'metas'    => array(
					'required'      => true,
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'display_name',
				),
			),
			array(
				'id'       => 8,
				'group_id' => $group_id,
				'type'     => 'user_website',
				'name'     => 'Website',
				'metas'    => array(
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'user_url',
				),
			),
			array(
				'id'       => 9,
				'group_id' => $group_id,
				'type'     => 'user_description',
				'name'     => 'Description',
				'metas'    => array(
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'description',
				),
			),
			array(
				'id'       => 10,
				'group_id' => $group_id,
				'type'     => 'user_avatar',
				'name'     => 'Profile picture',
				'metas'    => array(
					'visibility'    => 'public',
					'editing'       => 'public',
					'user_meta_key' => 'current_user_avatar',
				),
			),
		);

		$order = 0;

		foreach ( $fields as $field ) {

			++$order;
			$field['field_order'] = $order;

			$save_field = new WPUM_Field();
			$save_field->add( $field );

			foreach ( $field['metas'] as $meta_key => $meta_value ) {
				$save_field->add_meta( $meta_key, $meta_value );
			}

			$saved_fields[] = $save_field;
		}
	}

	return $saved_fields;
}

/**
 * Install the cover image field into the database.
 *
 * @return void
 */
function wpum_install_cover_image_field() {

	$group_id = 1;

	$field = array(
		'group_id' => $group_id,
		'type'     => 'user_cover',
		'name'     => 'Profile cover image',
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

	$types = array(
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
		'user_cover',
	);

	return apply_filters( 'wpum_get_primary_field_types', $types );
}

/**
 * Setup the tabs for the edit field dialog in the admin panel.
 *
 * @return array
 */
function wpum_get_edit_field_dialog_tabs() {

	$tabs = array(
		array(
			'id'   => 'fields',
			'name' => esc_html__( 'Fields', 'wp-user-manager' ),
		),
		array(
			'id'   => 'general',
			'name' => esc_html__( 'General', 'wp-user-manager' ),
		),
		array(
			'id'   => 'validation',
			'name' => esc_html__( 'Validation', 'wp-user-manager' ),
		),
		array(
			'id'   => 'privacy',
			'name' => esc_html__( 'Privacy', 'wp-user-manager' ),
		),
		array(
			'id'   => 'permissions',
			'name' => esc_html__( 'Permissions', 'wp-user-manager' ),
		),
		array(
			'id'   => 'appearance',
			'name' => esc_html__( 'Appearance', 'wp-user-manager' ),
		),
	);

	return apply_filters( 'wpum_get_fields_editor_edit_tabs', $tabs );
}

/**
 * Retrieve a list of registered field types and their field type groups.
 *
 * @return array
 */
function wpum_get_registered_field_types() {
	return WPUM()->field_types->get_registered_field_types();
}

/**
 * Use this function to start a loop of profile fields.
 *
 * @global $wpum_profile_fields
 * @param  array $args arguments to create the loop.
 * @return boolean       whether there's any group found.
 */
function wpum_has_profile_fields( $args = array() ) {

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
	echo esc_attr( wpum_get_field_group_id() );
}

/**
 * Return the name of a group within the loop.
 *
 * @global $wpum_fields_group
 * @return string
 */
function wpum_get_field_group_name() {
	global $wpum_fields_group;
	return apply_filters( 'wpum_get_field_group_name', $wpum_fields_group->get_name(), $wpum_fields_group->get_id() );
}

/**
 * Echo the name of a group within the loop.
 *
 * @return void
 */
function wpum_the_field_group_name() {
	echo wp_kses_post( wpum_get_field_group_name() );
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
	echo esc_attr( wpum_get_field_group_slug() );
}

/**
 * Retrieve the description of the group within the loop.
 *
 * @return string
 * @global $wpum_fields_group
 */
function wpum_get_field_group_description() {
	global $wpum_fields_group;

	return apply_filters( 'wpum_get_field_group_description', $wpum_fields_group->get_description(), $wpum_fields_group->get_id() );
}

/**
 * Echo the description of a field group within the loop.
 *
 * @return void
 */
function wpum_the_field_group_description() {
	echo wp_kses_post( wpum_get_field_group_description() );
}

/**
 * Whether the current group within the loop has fields.
 *
 * @param bool $ignore_hidden
 *
 * @return array the current group fields within the loop.
 * @global     $wpum_profile_fields
 */
function wpum_field_group_has_fields( $ignore_hidden = false ) {
	global $wpum_profile_fields;
	return $wpum_profile_fields->has_fields( $ignore_hidden );
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
	echo wp_kses_post( wpum_get_field_name() );
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
	echo wp_kses_post( wpum_get_field_description() );
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
function wpum_get_field_css_class( $class = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Public API, cannot rename.

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
function wpum_the_field_css_class( $class = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.classFound -- Public API, cannot rename.
	echo esc_attr( join( ' ', wpum_get_field_css_class( $class ) ) );
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
	echo wpum_get_the_field_value(); // phpcs:ignore
}

/**
 * Wrapper function for size_format - checks the max size of the avatar and cover field.
 *
 * @param string $field_name
 * @param string $custom_size in bytes
 *
 * @return string
 */
function wpum_max_upload_size( $field_name = '', $custom_size = false ) {

	// Default max upload size,
	$output = size_format( wp_max_upload_size() );

	// Check if the field is the avatar upload field and max size is defined,
	if ( 'user_avatar' === $field_name && defined( 'WPUM_MAX_AVATAR_SIZE' ) ) {
		$output = size_format( WPUM_MAX_AVATAR_SIZE );
	} elseif ( 'user_cover' === $field_name && defined( 'WPUM_MAX_COVER_SIZE' ) ) {
		$output = size_format( WPUM_MAX_COVER_SIZE );
	}

	if ( $custom_size ) {
		$output = size_format( intval( $custom_size ), 0 );
	}

	return $output;
}

/**
 * Retrieve a list of registered parent field types.
 *
 * @return array
 */
function wpum_get_registered_parent_field_types() {

	return apply_filters( 'wpum_registered_parent_field_types', array() );
}

/**
 * Safe unserialization that doesn't allow classes
 *
 * @param mixed $data Data that might be unserialized.
 *
 * @return mixed Unserialized data can be any type.
 */
function wpum_maybe_unserialize( $data ) {
	if ( is_serialized( $data ) ) { // Don't attempt to unserialize data that wasn't serialized going in.
		return @unserialize( trim( $data ), array( 'allowed_classes' => false ) ); // phpcs:ignore
	}

	return $data;
}

/**
 * Ensure a string is text not a serialized string
 *
 * @param string $data
 *
 * @return string
 */
function wpum_sanitize_text( $data ) {
	if ( ! is_string( $data ) || is_serialized( $data ) ) {
		return '';
	}

	return sanitize_text_field( $data );
}

/**
 * Retrieve the definition of a registered field type: name, icon, group and
 * editor settings.
 *
 * @param string $type
 *
 * @return array|false
 */
function wpum_get_registered_field_type( $type ) {
	if ( ! $type ) {
		return false;
	}

	$registered_fields = wpum_get_registered_field_types();
	$criteria          = array( 'type' => $type );

	// Look in the default, standard and advanced groups, in that order.
	foreach ( array( 'default', 'standard', 'advanced' ) as $group ) {
		if ( empty( $registered_fields[ $group ]['fields'] ) ) {
			continue;
		}

		$found = wp_list_filter( $registered_fields[ $group ]['fields'], $criteria );

		if ( ! empty( $found ) ) {
			return reset( $found );
		}
	}

	return false;
}

/**
 * Retrieve the editor settings a field doesn't use. Primary fields have a fixed
 * meta key, and some have no placeholder.
 *
 * @param WPUM_Field $field
 *
 * @return array Setting keys.
 */
function wpum_get_field_deregistered_settings( $field ) {
	$deregistered = array();

	if ( ! empty( $field->get_primary_id() ) ) {
		// All primary fields do not need the meta key setting.
		$deregistered[] = 'user_meta_key';

		switch ( $field->get_primary_id() ) {
			case 'username':
			case 'user_displayname':
			case 'user_avatar':
			case 'user_cover':
				$deregistered[] = 'placeholder';
				break;
		}
	}

	return $deregistered;
}

/**
 * Retrieve every editor setting for a field, from all the editor's tabs, keyed
 * by setting key. Settings the field doesn't use are left out, as in the editor.
 *
 * @param WPUM_Field $field
 *
 * @return array
 */
function wpum_get_field_settings_config( $field ) {
	$field_type = wpum_get_registered_field_type( $field->get_type() );

	if ( ! $field_type || empty( $field_type['settings'] ) || ! is_array( $field_type['settings'] ) ) {
		return array();
	}

	$settings = array();

	foreach ( $field_type['settings'] as $group => $group_settings ) {
		$group_settings = apply_filters( 'wpum_fields_editor_field_settings', $group_settings, $field_type, $group );

		foreach ( (array) $group_settings as $key => $setting ) {
			$settings[ $key ] = $setting;
		}
	}

	foreach ( wpum_get_field_deregistered_settings( $field ) as $setting_key ) {
		unset( $settings[ $setting_key ] );
	}

	return apply_filters( 'wpum_fields_editor_deregister_settings', $settings, $field->get_primary_id(), $field );
}

/**
 * Turn the meta key typed in the field editor into the stored user meta key.
 *
 * Custom fields store their values under a `wpum_` key, and file fields under
 * `wpum_file_field_`. A key that already starts with `wpum_` is kept as it is.
 *
 * @param WPUM_Field $field
 * @param string     $meta_key
 *
 * @return string
 */
function wpum_prepare_field_user_meta_key( $field, $meta_key ) {
	$meta_key = (string) $meta_key;

	if ( strpos( $meta_key, 'wpum_' ) !== 0 ) {
		$meta_key = 'wpum_' . $meta_key;
		$meta_key = $field->wpum_sanitize_key( $meta_key );
		if ( 'file' === $field->get_type() ) {
			$append_key = str_replace( 'wpum_file_field_', '', $meta_key );
			$append_key = str_replace( 'wpum_', '', $append_key );
			$meta_key   = 'wpum_file_field_' . $append_key;
		}
	}

	return $meta_key;
}

/**
 * Check whether another field already stores its values under a user meta key.
 *
 * @param string $meta_key         The full meta key, with its `wpum_` prefix.
 * @param int    $exclude_field_id Field to leave out of the check, usually the one being edited.
 * @param bool   $canonical        Compare the keys as the field model reads them, lowercase with
 *                                 other characters removed, rather than exactly. Default false.
 *
 * @return bool
 */
function wpum_field_user_meta_key_exists( $meta_key, $exclude_field_id = 0, $canonical = false ) {
	global $wpdb;

	if ( ! $canonical ) {
		$meta_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM {$wpdb->prefix}wpum_fieldmeta WHERE meta_key = 'user_meta_key' AND meta_value = %s AND wpum_field_id != %d", $meta_key, $exclude_field_id ) ); // phpcs:ignore

		return intval( $meta_count ) > 0;
	}

	// Sanitize as the field model does when it reads a key.
	$sanitize    = array( new WPUM_Field(), 'wpum_sanitize_key' );
	$stored_keys = $wpdb->get_col( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}wpum_fieldmeta WHERE meta_key = 'user_meta_key' AND wpum_field_id != %d", $exclude_field_id ) ); // phpcs:ignore

	return in_array( call_user_func( $sanitize, $meta_key ), array_map( $sanitize, $stored_keys ), true );
}

/**
 * Retrieve the values a field setting accepts, for settings with a fixed list.
 *
 * @param array $setting The setting from the field type's editor settings.
 *
 * @return array|false Values as strings, or false if the setting takes any value.
 */
function wpum_get_field_setting_allowed_values( $setting ) {
	if ( ! isset( $setting['type'] ) ) {
		return false;
	}

	if ( 'radios' === $setting['type'] && ! empty( $setting['values'] ) && is_array( $setting['values'] ) ) {
		return array_map( 'strval', wp_list_pluck( $setting['values'], 'value' ) );
	}

	// Select settings list their choices as `id` and `name` pairs.
	if ( 'select' === $setting['type'] && ! empty( $setting['values'] ) && is_array( $setting['values'] ) ) {
		$values = array();

		foreach ( $setting['values'] as $choice ) {
			if ( is_array( $choice ) ) {
				$values[] = (string) ( isset( $choice['id'] ) ? $choice['id'] : ( isset( $choice['value'] ) ? $choice['value'] : '' ) );
			} else {
				$values[] = (string) $choice;
			}
		}

		return $values;
	}

	if ( 'multiselect' === $setting['type'] && ! empty( $setting['options'] ) && is_array( $setting['options'] ) ) {
		$values = array();

		foreach ( $setting['options'] as $key => $option ) {
			$values[] = is_array( $option ) && isset( $option['value'] ) ? (string) $option['value'] : (string) $key;
		}

		return $values;
	}

	return false;
}

/**
 * Check field settings before they're saved.
 *
 * @param WPUM_Field $field
 * @param array      $data     Setting key => value.
 * @param array      $settings Editor settings for the field, keyed by setting key.
 *
 * @return true|WP_Error
 */
function wpum_validate_field_settings( $field, $data, $settings ) {
	foreach ( $data as $setting_id => $setting_data ) {
		$is_known = in_array( $setting_id, array( 'field_title', 'field_description' ), true ) || ( 'user_meta_key' === $setting_id && ! $field->is_primary() ) || isset( $settings[ $setting_id ] );

		if ( ! $is_known || in_array( $setting_id, apply_filters( 'wpum_update_field_excluded_settings', array( 'conditions' ) ), true ) ) {
			// translators: %s setting key
			return new WP_Error( 'wpum_field_unknown_setting', sprintf( esc_html__( 'This field has no %s setting that can be updated here.', 'wp-user-manager' ), esc_html( $setting_id ) ), array( 'status' => 400 ) );
		}

		if ( 'field_title' === $setting_id && ( ! is_scalar( $setting_data ) || '' === sanitize_text_field( (string) $setting_data ) ) ) {
			return new WP_Error( 'wpum_field_invalid_title', esc_html__( 'The field needs a title.', 'wp-user-manager' ), array( 'status' => 400 ) );
		}

		if ( 'user_meta_key' === $setting_id ) {
			if ( ! is_string( $setting_data ) || '' === sanitize_key( $setting_data ) ) {
				return new WP_Error( 'wpum_field_invalid_meta_key', esc_html__( 'The field needs a meta key.', 'wp-user-manager' ), array( 'status' => 400 ) );
			}

			$meta_key = wpum_prepare_field_user_meta_key( $field, $setting_data );

			// The field model sanitizes the key when it reads it, so a key it would change is refused.
			if ( $field->wpum_sanitize_key( $meta_key ) !== $meta_key ) {
				return new WP_Error( 'wpum_field_invalid_meta_key', esc_html__( 'The meta key can only use lowercase letters, numbers, dashes and underscores.', 'wp-user-manager' ), array( 'status' => 400 ) );
			}

			if ( wpum_field_user_meta_key_exists( $meta_key, $field->get_ID(), true ) ) {
				return new WP_Error( 'wpum_field_duplicate_meta_key', esc_html__( 'The user meta key must be unique for each field.', 'wp-user-manager' ), array( 'status' => 400 ) );
			}

			continue;
		}

		$setting_type = isset( $settings[ $setting_id ]['type'] ) ? $settings[ $setting_id ]['type'] : '';

		// A multiselect takes a list. Every other setting is saved as a single value, so
		// a list would be lost, unless an addon's sanitize method accepts one.
		$single_value = 'multiselect' !== $setting_type && (
			in_array( $setting_type, array( 'input', 'radios', 'textarea', 'textArea', 'checkbox' ), true )
			|| 'sanitize_text_field' === apply_filters( 'wpum_update_fields_sanitize_method', 'sanitize_text_field', $setting_type, $setting_data )
		);
		$wrong_shape  = ( 'multiselect' === $setting_type && ! is_array( $setting_data ) && ! in_array( $setting_data, array( '', null ), true ) )
			|| ( $single_value && is_array( $setting_data ) );

		if ( $wrong_shape ) {
			// translators: %s setting key
			return new WP_Error( 'wpum_field_invalid_value', sprintf( esc_html__( 'The %s setting takes a different kind of value.', 'wp-user-manager' ), esc_html( $setting_id ) ), array( 'status' => 400 ) );
		}

		$allowed = isset( $settings[ $setting_id ] ) ? wpum_get_field_setting_allowed_values( $settings[ $setting_id ] ) : false;

		if ( false === $allowed ) {
			continue;
		}

		foreach ( (array) $setting_data as $value ) {
			if ( '' !== $value && ! in_array( (string) $value, $allowed, true ) ) {
				// translators: %1$s value, %2$s setting key
				return new WP_Error( 'wpum_field_invalid_option', sprintf( esc_html__( '%1$s is not an option for the %2$s setting.', 'wp-user-manager' ), esc_html( $value ), esc_html( $setting_id ) ), array( 'status' => 400 ) );
			}
		}
	}

	return true;
}

/**
 * Update a field's name, description, meta key and settings.
 *
 * Values are sanitized by the type of each setting, as the fields editor does.
 * An empty value deletes the setting.
 *
 * @param int   $field_id
 * @param array $data Setting key => value, e.g. `field_title`, `field_description`,
 *                    `user_meta_key`, `required`, `visibility`, `placeholder`.
 * @param array $args {
 *     @type array|null $settings         The editor settings to sanitize against, keyed or listed
 *                                        with a `model` key. Default null: every setting of the
 *                                        field's type.
 *     @type array|null $dropdown_options Options for dropdown, multiselect, multicheckbox and
 *                                        radio fields: a list of `value` and `label` pairs.
 *     @type bool       $strict           Return an error, and save nothing, for an unknown
 *                                        setting, an empty title or meta key, a meta key used by
 *                                        another field, or a value outside a setting's options.
 *                                        When false, unknown settings are skipped. Default false.
 * }
 *
 * @return true|WP_Error
 */
function wpum_update_field_settings( $field_id, $data, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'settings'         => null,
		'dropdown_options' => null,
		'strict'           => false,
	) );

	$field_to_update = new WPUM_Field( $field_id );

	if ( ! $field_to_update->exists() ) {
		return new WP_Error( 'wpum_field_not_found', esc_html__( 'The field does not exist.', 'wp-user-manager' ), array( 'status' => 404 ) );
	}

	$data           = is_array( $data ) ? $data : array();
	$setting_fields = null === $args['settings'] ? wpum_get_field_settings_config( $field_to_update ) : (array) $args['settings'];

	if ( $args['strict'] ) {
		$settings_by_key = array();

		foreach ( $setting_fields as $setting_field ) {
			if ( is_array( $setting_field ) && isset( $setting_field['model'] ) ) {
				$settings_by_key[ $setting_field['model'] ] = $setting_field;
			}
		}

		$valid = wpum_validate_field_settings( $field_to_update, $data, $settings_by_key );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
	}

	foreach ( $data as $setting_id => $setting_data ) {
		if ( in_array( $setting_id, apply_filters( 'wpum_update_field_excluded_settings', array( 'conditions' ) ), true ) ) {
			continue;
		}

		// Update the name and description.
		if ( 'field_title' === $setting_id ) {
			$field_to_update->update( array( 'name' => sanitize_text_field( $setting_data ) ) );
		} elseif ( 'field_description' === $setting_id ) {
			$field_to_update->update( array( 'description' => wp_kses_post( $setting_data ) ) );
			// Now update the meta data.
		} elseif ( 'user_meta_key' === $setting_id && ! $field_to_update->is_primary() ) {
			$field_to_update->update_meta( $setting_id, wpum_prepare_field_user_meta_key( $field_to_update, $setting_data ) );
		} else {

			// Find the type of input for this setting.
			$criteria       = array( 'model' => $setting_id );
			$setting_config = wp_list_filter( $setting_fields, $criteria );

			// Find the first key of the array within the setting config array.
			reset( $setting_config );
			$first_key      = key( $setting_config );
			$setting_config = isset( $setting_config[ $first_key ] ) ? $setting_config[ $first_key ] : false;

			if ( is_array( $setting_config ) && array_key_exists( 'type', $setting_config ) ) {

				$setting_type = $setting_config['type'];

				switch ( $setting_type ) {
					case 'input':
					case 'radios':
						$setting_data = sanitize_text_field( $setting_data );
						break;
					case 'textarea':
						$setting_data = wp_kses_post( $setting_data );
						break;
					case 'checkbox':
						$setting_data = 'true' === $setting_data || true === $setting_data;
						break;
					case 'multiselect':
						break;
					default:
						$sanitize_method = apply_filters( 'wpum_update_fields_sanitize_method', 'sanitize_text_field', $setting_type, $setting_data );
						$setting_data    = call_user_func( $sanitize_method, $setting_data );
						break;
				}

				// Now finally save the data.
				if ( $setting_data ) {
					$field_to_update->update_meta( $setting_id, $setting_data );
				} else {
					$field_to_update->delete_meta( $setting_id );
				}
			}
		}
	}

	$dropdown_options = $args['dropdown_options'];

	if ( is_array( $dropdown_options ) && ! empty( $dropdown_options ) ) {
		$options = array();
		foreach ( $dropdown_options as $key => $value ) {
			$options[ $key ] = array(
				'value' => sanitize_text_field( isset( $value['value'] ) ? $value['value'] : '' ),
				'label' => sanitize_text_field( isset( $value['label'] ) ? $value['label'] : '' ),
			);
		}
		$field_to_update->update_meta( 'dropdown_options', $options );
	}

	wpum_delete_field_group_fields_cache( $field_to_update->get_group_id() );

	return true;
}

/**
 * Delete the cached list of fields in a group.
 *
 * @param int $group_id
 * @param int $parent_id Parent field ID, for the child fields of a repeater.
 *
 * @return void
 */
function wpum_delete_field_group_fields_cache( $group_id, $parent_id = 0 ) {
	$args = array(
		'group_id' => (int) $group_id,
		'orderby'  => 'field_order',
		'order'    => 'ASC',
		'parent'   => $parent_id,
	);

	$cache_key = WPUM()->fields->get_cache_key_from_args( $args );

	wp_cache_delete( $cache_key, WPUM()->fields->cache_group );
}
