<?php
/**
 * Functions collection to work with directories.
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
 * Retrieve the list of options for the sort by dropdown on the user directory.
 *
 * @return array
 */
function wpum_get_directory_sort_by_methods() {

	$options = array(
		'newest'    => esc_html__( 'Newest users first', 'wp-user-manager' ),
		'oldest'    => esc_html__( 'Oldest users first', 'wp-user-manager' ),
		'name'      => esc_html__( 'First name', 'wp-user-manager' ),
		'last_name' => esc_html__( 'Last Name', 'wp-user-manager' ),
	);

	return apply_filters( 'wpum_get_directory_sort_by_methods', $options );
}

/**
 * Get the sort method to use for a directory, from the one a visitor asked for.
 *
 * @param string $requested The sort method in the request.
 * @param string $fallback  The directory's default sort method.
 *
 * @return string
 */
function wpum_get_directory_sort_method( $requested, $fallback ) {
	return array_key_exists( $requested, wpum_get_directory_sort_by_methods() ) ? $requested : $fallback;
}

/**
 * Get the user table columns a directory search looks in.
 *
 * Only the display name is on show in a directory. Email addresses aren't public, and
 * neither is a username, which is the email address for anyone who registered without one.
 *
 * @param int $directory_id
 *
 * @return array
 */
function wpum_get_directory_search_columns( $directory_id ) {
	$columns = array( 'display_name' );

	if ( current_user_can( 'list_users' ) ) {
		$columns = array( 'ID', 'user_login', 'user_url', 'user_email', 'user_nicename', 'display_name' );
	}

	$columns = apply_filters( 'wpum_directory_search_columns', $columns, $directory_id );

	// With no columns, WP_User_Query picks its own, and those include the email address.
	return ! empty( $columns ) ? $columns : array( 'display_name' );
}

/**
 * Stop a directory search matching the email address in a display name, when the
 * site masks those addresses.
 *
 * @param string $where The WHERE clause of the user query.
 *
 * @return string
 */
function wpum_exclude_masked_display_names_from_search( $where ) {
	if ( ! wpum_get_option( 'obfuscate_display_name_emails' ) ) {
		return $where;
	}

	// The search term can't contain this text: esc_like() has escaped its underscores.
	return str_replace( 'display_name LIKE', "display_name NOT REGEXP '^[^ @]+@[^ @]+[.][^ @]+$' AND display_name LIKE", $where );
}

/**
 * Retrieve a list of options for the per page amount modifier for the directory.
 *
 * @return array
 */
function wpum_get_directory_amount_modifier() {

	$amounts = array(
		''   => '',
		'1'  => '1',
		'10' => '10',
		'15' => '15',
		'20' => '20',
	);

	return apply_filters( 'wpum_get_directory_amount_modifier', $amounts );
}

/**
 * Defines the list of available directory templates.
 *
 * @return array
 */
function wpum_get_directory_templates() {

	$templates = array(
		'default' => esc_html__( 'Default template', 'wp-user-manager' ),
	);

	return apply_filters( 'wpum_get_directory_templates', $templates );
}

/**
 * Defines the list of available directory user templates.
 *
 * @return array
 */
function wpum_get_directory_user_templates() {

	$templates = array(
		'default' => esc_html__( 'Default template', 'wp-user-manager' ),
	);

	return apply_filters( 'wpum_get_directory_user_templates', $templates );
}

/**
 * Display pagination for user directory.
 *
 * @param object $data
 * @return void
 */
function wpum_user_directory_pagination( $data ) {

	echo '<div class="wpum-directory-pagination">';

	$big          = 9999999;
	$search_for   = array( $big, '#038;' );
	$replace_with = array( '%#%', '&' );

	echo wp_kses_post( paginate_links( array(
		'base'      => str_replace( $search_for, $replace_with, esc_url( get_pagenum_link( $big ) ) ),
		'current'   => $data->paged,
		'total'     => $data->total_pages,
		'prev_text' => __( 'Previous page', 'wp-user-manager' ),
		'next_text' => __( 'Next page', 'wp-user-manager' ),
	) ) );

	echo '</div>';
}

/**
 * Retrieve the directory settings fields, keyed by setting name.
 *
 * @return array Setting name => Carbon Fields field.
 */
function wpum_get_directory_settings_fields() {
	$fields = array();

	foreach ( WPUM()->directories_editor->get_directory_settings_fields() as $group_fields ) {
		foreach ( (array) $group_fields as $field ) {
			if ( is_object( $field ) && method_exists( $field, 'get_base_name' ) ) {
				$fields[ $field->get_base_name() ] = $field;
			}
		}
	}

	return $fields;
}

/**
 * Check that Carbon Fields has registered its fields, so directory settings can
 * be built, read and saved.
 *
 * @return true|WP_Error
 */
function wpum_check_directory_settings_ready() {
	if ( ! did_action( 'carbon_fields_fields_registered' ) ) {
		return new WP_Error( 'wpum_directory_settings_not_ready', esc_html__( 'Directory settings cannot be read or saved before WordPress has loaded.', 'wp-user-manager' ), array( 'status' => 500 ) );
	}

	return true;
}

/**
 * Make sure the directory settings are registered with Carbon Fields, which only
 * happens on the directory edit screen and when a directory is displayed.
 *
 * @param array $setting_names
 *
 * @return true|WP_Error
 */
function wpum_ensure_directory_settings_registered( $setting_names ) {
	$ready = wpum_check_directory_settings_ready();

	if ( is_wp_error( $ready ) ) {
		return $ready;
	}

	$registered = true;

	foreach ( $setting_names as $setting_name ) {
		if ( ! \WPUM\Carbon_Fields\Helper\Helper::get_field( 'post_meta', '', $setting_name ) ) {
			$registered = false;
			break;
		}
	}

	if ( ! $registered ) {
		WPUM()->directories_editor->register_directory_settings();
	}

	return true;
}

/**
 * Check and sanitize directory settings.
 *
 * Checkboxes take a boolean, select settings one of their options, and
 * multiselect settings a list of their options. `directory_profiles_per_page`
 * must be a positive number and `directory_excluded_users` a list of user IDs.
 *
 * @param array $settings Setting name => value, e.g. `directory_assigned_roles`,
 *                        `directory_search_form`, `directory_sorting_method`.
 *
 * @return array|WP_Error The settings ready to save.
 */
function wpum_prepare_directory_settings( $settings ) {
	if ( ! is_array( $settings ) ) {
		return new WP_Error( 'wpum_directory_invalid_settings', esc_html__( 'Directory settings must be an array.', 'wp-user-manager' ), array( 'status' => 400 ) );
	}

	// Building the fields needs Carbon Fields to be ready.
	$ready = wpum_check_directory_settings_ready();

	if ( is_wp_error( $ready ) ) {
		return $ready;
	}

	$fields   = wpum_get_directory_settings_fields();
	$prepared = array();

	foreach ( $settings as $name => $value ) {
		if ( ! isset( $fields[ $name ] ) ) {
			// translators: %s setting name
			return new WP_Error( 'wpum_directory_unknown_setting', sprintf( esc_html__( 'Unknown directory setting: %s', 'wp-user-manager' ), esc_html( $name ) ), array( 'status' => 400 ) );
		}

		$field = $fields[ $name ];
		// translators: %s setting name
		$invalid = new WP_Error( 'wpum_directory_invalid_setting', sprintf( esc_html__( 'Invalid value for the %s directory setting.', 'wp-user-manager' ), esc_html( $name ) ), array( 'status' => 400 ) );

		switch ( $field->get_type() ) {
			case 'checkbox':
				$truthy = array( true, 1, '1', 'true', 'yes', $field->get_option_value() );
				$falsy  = array( false, 0, '0', 'false', 'no', '' );

				if ( in_array( $value, $truthy, true ) ) {
					$prepared[ $name ] = $field->get_option_value();
				} elseif ( in_array( $value, $falsy, true ) || null === $value ) {
					$prepared[ $name ] = '';
				} else {
					return $invalid;
				}
				break;

			case 'select':
				$options = array_map( 'strval', array_keys( $field->get_options() ) );

				if ( ! is_scalar( $value ) || ! in_array( (string) $value, $options, true ) ) {
					return $invalid;
				}

				$prepared[ $name ] = (string) $value;
				break;

			case 'multiselect':
				$options = array_map( 'strval', array_keys( $field->get_options() ) );
				$values  = is_string( $value ) ? array_filter( array_map( 'trim', explode( ',', $value ) ), 'strlen' ) : $value;

				if ( ! is_array( $values ) ) {
					return $invalid;
				}

				foreach ( $values as $item ) {
					if ( ! is_scalar( $item ) || ! in_array( (string) $item, $options, true ) ) {
						return $invalid;
					}
				}

				$prepared[ $name ] = array_values( array_map( 'strval', $values ) );
				break;

			case 'text':
				if ( ! is_scalar( $value ) && null !== $value ) {
					return $invalid;
				}

				$value = sanitize_text_field( (string) $value );

				if ( 'directory_profiles_per_page' === $name && '' !== $value ) {
					if ( ! preg_match( '/^\d+$/', $value ) || (int) $value < 1 ) {
						return $invalid;
					}

					$value = (string) (int) $value;
				}

				if ( 'directory_excluded_users' === $name && '' !== $value ) {
					$ids = array_map( 'trim', explode( ',', $value ) );

					foreach ( $ids as $id ) {
						if ( ! preg_match( '/^\d+$/', $id ) ) {
							return $invalid;
						}
					}

					$value = implode( ', ', array_map( 'intval', $ids ) );
				}

				$prepared[ $name ] = $value;
				break;

			default:
				// translators: %s setting name
				return new WP_Error( 'wpum_directory_unsupported_setting', sprintf( esc_html__( 'The %s directory setting can only be changed in the directory editor.', 'wp-user-manager' ), esc_html( $name ) ), array( 'status' => 400 ) );
		}
	}

	return $prepared;
}

/**
 * Load a directory, or return an error if it doesn't exist.
 *
 * @param int $directory_id
 *
 * @return WP_Post|WP_Error
 */
function wpum_get_directory_or_error( $directory_id ) {
	$directory_id = is_numeric( $directory_id ) ? absint( $directory_id ) : 0;

	// get_post() falls back to the current post for 0, so don't look that up.
	$directory = $directory_id ? get_post( $directory_id ) : null;

	if ( ! $directory || 'wpum_directory' !== $directory->post_type ) {
		return new WP_Error( 'wpum_directory_not_found', esc_html__( 'The directory does not exist.', 'wp-user-manager' ), array( 'status' => 404 ) );
	}

	return $directory;
}

/**
 * Retrieve a directory's settings, with defaults for settings never saved.
 *
 * @param int $directory_id
 *
 * @return array|WP_Error Setting name => value.
 */
function wpum_get_directory_settings( $directory_id ) {
	$directory = wpum_get_directory_or_error( $directory_id );

	if ( is_wp_error( $directory ) ) {
		return $directory;
	}

	$ready = wpum_check_directory_settings_ready();

	if ( is_wp_error( $ready ) ) {
		return $ready;
	}

	$names      = array_keys( wpum_get_directory_settings_fields() );
	$registered = wpum_ensure_directory_settings_registered( $names );

	if ( is_wp_error( $registered ) ) {
		return $registered;
	}

	$settings = array();

	foreach ( $names as $name ) {
		$settings[ $name ] = \WPUM\carbon_get_post_meta( $directory->ID, $name );
	}

	return $settings;
}

/**
 * Change some of a directory's settings. Settings not given are left as they are.
 *
 * Nothing is saved if any setting is invalid.
 *
 * @param int   $directory_id
 * @param array $settings Setting name => value. See wpum_prepare_directory_settings().
 *
 * @return true|WP_Error
 */
function wpum_update_directory_settings( $directory_id, $settings ) {
	$directory = wpum_get_directory_or_error( $directory_id );

	if ( is_wp_error( $directory ) ) {
		return $directory;
	}

	$prepared = wpum_prepare_directory_settings( $settings );

	if ( is_wp_error( $prepared ) ) {
		return $prepared;
	}

	$registered = wpum_ensure_directory_settings_registered( array_keys( $prepared ) );

	if ( is_wp_error( $registered ) ) {
		return $registered;
	}

	foreach ( $prepared as $name => $value ) {
		\WPUM\carbon_set_post_meta( $directory->ID, $name, $value );
	}

	return true;
}

/**
 * Create a published user directory.
 *
 * @param string $title
 * @param array  $settings Setting name => value. See wpum_prepare_directory_settings().
 *
 * @return int|WP_Error The directory ID.
 */
function wpum_create_directory( $title, $settings = array() ) {
	$title = is_scalar( $title ) ? sanitize_text_field( (string) $title ) : '';

	if ( '' === $title ) {
		return new WP_Error( 'wpum_directory_invalid_title', esc_html__( 'The directory needs a title.', 'wp-user-manager' ), array( 'status' => 400 ) );
	}

	// Check the settings before creating anything.
	$prepared = wpum_prepare_directory_settings( $settings );

	if ( is_wp_error( $prepared ) ) {
		return $prepared;
	}

	$directory_id = wp_insert_post( array(
		// wp_insert_post() unslashes its input.
		'post_title'  => wp_slash( $title ),
		'post_type'   => 'wpum_directory',
		'post_status' => 'publish',
	), true );

	if ( is_wp_error( $directory_id ) ) {
		return $directory_id;
	}

	if ( ! empty( $prepared ) ) {
		$updated = wpum_update_directory_settings( $directory_id, $prepared );

		if ( is_wp_error( $updated ) ) {
			wp_delete_post( $directory_id, true );

			return $updated;
		}
	}

	return $directory_id;
}
