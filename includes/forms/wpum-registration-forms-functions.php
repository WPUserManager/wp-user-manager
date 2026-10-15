<?php
/**
 * Functions to update registration forms.
 *
 * The registration forms editor calls these from its ajax handlers, and they
 * can be called directly with plain arrays. They don't check permissions:
 * the caller does.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2026, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the primary fields that can't be added to a registration form.
 *
 * @return array
 */
function wpum_get_registration_form_non_allowed_fields() {
	$non_allowed_fields = array(
		'user_nickname',
		'user_displayname',
	);

	if ( ! wpum_get_option( 'custom_avatars' ) ) {
		$non_allowed_fields[] = 'user_avatar';
	}

	if ( wpum_get_option( 'disable_profile_cover' ) ) {
		$non_allowed_fields[] = 'user_cover';
	}

	return apply_filters( 'wpum_non_allowed_fields', $non_allowed_fields );
}

/**
 * Load a registration form, or return an error if it doesn't exist.
 *
 * @param int $form_id
 *
 * @return WPUM_Registration_Form|WP_Error
 */
function wpum_get_registration_form_or_error( $form_id ) {
	$form_id = is_numeric( $form_id ) ? (int) $form_id : 0;
	$form    = $form_id > 0 ? new WPUM_Registration_Form( $form_id ) : false;

	if ( ! $form || ! $form->exists() ) {
		return new WP_Error( 'wpum_registration_form_not_found', esc_html__( 'The registration form does not exist.', 'wp-user-manager' ), array( 'status' => 404 ) );
	}

	return $form;
}

/**
 * Rename a registration form.
 *
 * @param int    $form_id
 * @param string $name
 *
 * @return true|WP_Error
 */
function wpum_update_registration_form_name( $form_id, $name ) {
	$name = is_scalar( $name ) ? sanitize_text_field( (string) $name ) : '';

	if ( ! $name ) {
		return new WP_Error( 'wpum_registration_form_invalid_name', esc_html__( 'The registration form needs a name.', 'wp-user-manager' ), array( 'status' => 400 ) );
	}

	$form = wpum_get_registration_form_or_error( $form_id );

	if ( is_wp_error( $form ) ) {
		return $form;
	}

	$data = apply_filters( 'wpum_registration_form_update', array(
		'name' => $name,
	), (int) $form->get_ID() );

	WPUM()->registration_forms->update( (int) $form->get_ID(), $data );

	wpum_delete_registration_forms_cache();

	return true;
}

/**
 * Set the fields of a registration form, in order.
 *
 * Each item is a field ID, or an array with an `id` key as the forms editor
 * sends it. Addons map their own IDs with the
 * `wpum_save_registration_form_field_id` filter.
 *
 * @param int   $form_id
 * @param array $fields
 * @param array $args {
 *     @type bool $strict Return an error for a field that doesn't exist, can't be
 *                        used in a registration form, or is listed twice. Default false.
 * }
 *
 * @return true|WP_Error
 */
function wpum_update_registration_form_fields( $form_id, $fields, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'strict' => false,
	) );

	$form = wpum_get_registration_form_or_error( $form_id );

	if ( is_wp_error( $form ) ) {
		return $form;
	}

	$fields         = is_array( $fields ) ? $fields : array();
	$fields_to_save = array();

	foreach ( $fields as $field ) {
		if ( ! is_array( $field ) ) {
			$field = array(
				'id' => $field,
			);
		}

		$field_id = isset( $field['id'] ) ? $field['id'] : null;

		$fields_to_save[] = apply_filters( 'wpum_save_registration_form_field_id', absint( $field_id ), $field_id, $field );
	}

	if ( empty( $fields_to_save ) ) {
		return new WP_Error( 'wpum_registration_form_no_fields', esc_html__( 'A registration form needs at least one field.', 'wp-user-manager' ), array( 'status' => 400 ) );
	}

	if ( $args['strict'] ) {
		$valid = wpum_validate_registration_form_fields( $fields_to_save );

		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
	}

	$form->update_meta( 'fields', $fields_to_save );

	wpum_delete_registration_forms_cache();

	return true;
}

/**
 * Check that a list of field IDs can be saved to a registration form.
 *
 * @param array $field_ids
 *
 * @return true|WP_Error
 */
function wpum_validate_registration_form_fields( $field_ids ) {
	$non_allowed_fields = wpum_get_registration_form_non_allowed_fields();
	$seen               = array();

	foreach ( (array) $field_ids as $field_id ) {
		if ( in_array( (string) $field_id, $seen, true ) ) {
			// translators: %s field ID
			return new WP_Error( 'wpum_registration_form_duplicate_field', sprintf( esc_html__( 'Field %s is listed more than once.', 'wp-user-manager' ), esc_html( $field_id ) ), array( 'status' => 400 ) );
		}

		$seen[] = (string) $field_id;

		$field    = new WPUM_Field( $field_id );
		$is_valid = $field->exists() && class_exists( 'WPUM_Field_' . ucfirst( $field->get_type() ) );

		if ( ! apply_filters( 'wpum_registration_form_valid_field', $is_valid, $field_id ) ) {
			// translators: %s field ID
			return new WP_Error( 'wpum_registration_form_invalid_field', sprintf( esc_html__( 'Field %s does not exist.', 'wp-user-manager' ), esc_html( $field_id ) ), array( 'status' => 400 ) );
		}

		$primary_id = $field->exists() ? $field->get_primary_id() : '';

		if ( ! empty( $primary_id ) && in_array( $primary_id, $non_allowed_fields, true ) ) {
			// translators: %s field name
			return new WP_Error( 'wpum_registration_form_field_not_allowed', sprintf( esc_html__( 'The %s field cannot be used in a registration form.', 'wp-user-manager' ), esc_html( $field->get_name() ) ), array( 'status' => 400 ) );
		}
	}

	return true;
}

/**
 * Save the settings of a registration form: role, redirect, Stripe products and
 * any setting an addon registers for the form.
 *
 * Values are sanitized with the `wpum_form_settings_sanitize_{type}` filters.
 * The administrator role, or a role that doesn't exist, is never saved.
 *
 * @param int   $form_id
 * @param array $settings Setting ID => value.
 * @param array $args {
 *     @type bool $partial Only change the settings given. When false, as in the forms
 *                         editor, a stored setting missing from $settings is deleted.
 *                         Default false.
 *     @type bool $strict  Return an error, and save nothing, for an unknown setting,
 *                         a role that can't be assigned, or a value that isn't one of
 *                         the setting's options. When false, those are skipped. Default false.
 * }
 *
 * @return true|WP_Error
 */
function wpum_update_registration_form_settings( $form_id, $settings, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'partial' => false,
		'strict'  => false,
	) );

	$form = wpum_get_registration_form_or_error( $form_id );

	if ( is_wp_error( $form ) ) {
		return $form;
	}

	$settings            = is_array( $settings ) ? $settings : array();
	$registered_settings = array();

	foreach ( $form->get_settings_options() as $registered_setting ) {
		$registered_settings[ $registered_setting['id'] ] = $registered_setting;
	}

	$values = array();

	foreach ( $settings as $key => $value ) {
		if ( ! isset( $registered_settings[ $key ] ) ) {
			if ( $args['strict'] ) {
				// translators: %s setting ID
				return new WP_Error( 'wpum_registration_form_unknown_setting', sprintf( esc_html__( 'Unknown registration form setting: %s', 'wp-user-manager' ), esc_html( $key ) ), array( 'status' => 400 ) );
			}

			continue;
		}

		$values[ $key ] = apply_filters( 'wpum_form_settings_sanitize_' . $registered_settings[ $key ]['type'], $value );

		if ( $args['strict'] ) {
			$valid = wpum_validate_registration_form_setting( $registered_settings[ $key ], $values[ $key ] );

			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}
	}

	$stored_settings_model = $form->get_settings_model();

	if ( ! $args['partial'] ) {
		$deleted_settings = array_diff_key( $stored_settings_model, $settings );

		foreach ( $deleted_settings as $deleted_key => $deleted_setting ) {
			$form->delete_meta( $deleted_key, '' );
		}
	}

	foreach ( $values as $key => $value ) {
		if ( isset( $stored_settings_model[ $key ] ) && $value === $stored_settings_model[ $key ] ) {
			// Setting not changed
			continue;
		}

		if ( 'role' === $key && ! wpum_is_assignable_registration_role( $value ) ) {
			// Illegal role selection;
			continue;
		}

		$form->update_meta( $key, $value );
	}

	return true;
}

/**
 * Check whether a sanitized role setting can be saved to a registration form.
 *
 * Registration uses the first role, so only that one is checked, as the forms
 * editor always has.
 *
 * @param mixed $value
 *
 * @return bool
 */
function wpum_is_assignable_registration_role( $value ) {
	if ( is_array( $value ) && ! empty( $value ) && ( 'administrator' === $value[0] || ! get_role( $value[0] ) ) ) {
		return false;
	}

	return true;
}

/**
 * Check a sanitized registration form setting value against the setting.
 *
 * @param array $setting The registered setting.
 * @param mixed $value
 *
 * @return true|WP_Error
 */
function wpum_validate_registration_form_setting( $setting, $value ) {
	if ( 'role' === $setting['id'] ) {
		foreach ( (array) $value as $role ) {
			if ( ! wpum_is_assignable_registration_role( array( $role ) ) ) {
				// translators: %s role key
				return new WP_Error( 'wpum_registration_form_invalid_role', sprintf( esc_html__( 'The %s role cannot be assigned at registration.', 'wp-user-manager' ), esc_html( $role ) ), array( 'status' => 400 ) );
			}
		}
	}

	// An empty options list allows no value, e.g. Stripe products before any are fetched.
	$has_options = in_array( $setting['type'], array( 'select', 'multiselect', 'radio', 'multicheckbox' ), true ) && isset( $setting['options'] ) && is_array( $setting['options'] );

	if ( ! $has_options ) {
		return true;
	}

	$allowed = wpum_get_setting_option_values( $setting['options'] );

	foreach ( (array) $value as $item ) {
		if ( '' === $item || null === $item ) {
			continue;
		}

		if ( ! in_array( (string) $item, $allowed, true ) ) {
			// translators: %1$s value, %2$s setting ID
			return new WP_Error( 'wpum_registration_form_invalid_option', sprintf( esc_html__( '%1$s is not an option for the %2$s setting.', 'wp-user-manager' ), esc_html( $item ), esc_html( $setting['id'] ) ), array( 'status' => 400 ) );
		}
	}

	return true;
}

/**
 * Retrieve the values from a setting's options, which are either
 * value => label pairs or a list of arrays with `value` and `label` keys.
 *
 * @param array $options
 *
 * @return array Values as strings.
 */
function wpum_get_setting_option_values( $options ) {
	$values = array();

	foreach ( (array) $options as $key => $option ) {
		if ( is_array( $option ) && isset( $option['value'] ) ) {
			$values[] = (string) $option['value'];
		} else {
			$values[] = (string) $key;
		}
	}

	return $values;
}

/**
 * Delete the cached registration forms lists.
 *
 * @return void
 */
function wpum_delete_registration_forms_cache() {
	// Both the default query and the unlimited query used by the forms list are cached.
	$cache_keys = array(
		WPUM()->registration_forms->get_cache_key_from_args(),
		WPUM()->registration_forms->get_cache_key_from_args( array(
			'number' => -1,
		) ),
	);

	foreach ( $cache_keys as $cache_key ) {
		wp_cache_delete( $cache_key, WPUM()->registration_forms->cache_group );
	}
}
add_action( 'wpum_registration_form_insert', 'wpum_delete_registration_forms_cache', 10, 0 );
add_action( 'wpum_before_registration_form_delete', 'wpum_delete_registration_forms_cache', 10, 0 );
add_action( 'wpum_registration_form_duplicated', 'wpum_delete_registration_forms_cache', 10, 0 );

/**
 * Sanitize a text setting.
 *
 * @param string $input
 *
 * @return string
 */
function wpum_sanitize_form_setting_text( $input ) {
	return trim( wp_strip_all_tags( $input, true ) );
}
add_filter( 'wpum_form_settings_sanitize_text', 'wpum_sanitize_form_setting_text' );
add_filter( 'wpum_form_settings_sanitize_radio', 'wpum_sanitize_form_setting_text' );
add_filter( 'wpum_form_settings_sanitize_select', 'wpum_sanitize_form_setting_text' );

/**
 * Sanitize a textarea setting.
 *
 * @param string $input
 *
 * @return string
 */
function wpum_sanitize_form_setting_textarea( $input ) {
	return stripslashes( wp_kses_post( $input ) );
}
add_filter( 'wpum_form_settings_sanitize_textarea', 'wpum_sanitize_form_setting_textarea' );

/**
 * Sanitize a multiselect or multicheckbox setting.
 *
 * @param mixed $input
 *
 * @return array
 */
function wpum_sanitize_form_setting_multiple( $input ) {
	$new_input = array();

	if ( is_array( $input ) && ! empty( $input ) ) {
		foreach ( $input as $key => $value ) {
			$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}
	}

	if ( ! empty( $input ) && ! is_array( $input ) ) {
		$input = explode( ',', $input );
		foreach ( $input as $key => $value ) {
			$new_input[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}
	}

	return $new_input;
}
add_filter( 'wpum_form_settings_sanitize_multiselect', 'wpum_sanitize_form_setting_multiple' );
add_filter( 'wpum_form_settings_sanitize_multicheckbox', 'wpum_sanitize_form_setting_multiple' );

/**
 * Sanitize the URL of a file setting.
 *
 * @param string $input
 *
 * @return string
 */
function wpum_sanitize_form_setting_file( $input ) {
	return esc_url( $input );
}
add_filter( 'wpum_form_settings_sanitize_file', 'wpum_sanitize_form_setting_file' );

/**
 * Sanitize a checkbox setting.
 *
 * @param mixed $input
 *
 * @return bool
 */
function wpum_sanitize_form_setting_checkbox( $input ) {
	if ( 'true' === $input ) {
		return true;
	}

	if ( 1 === (int) $input ) {
		return true;
	}

	return false;
}
add_filter( 'wpum_form_settings_sanitize_checkbox', 'wpum_sanitize_form_setting_checkbox' );
