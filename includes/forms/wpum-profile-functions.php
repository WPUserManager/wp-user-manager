<?php
/**
 * Functions to validate and save a user's profile values.
 *
 * The account form calls these, and they can be called directly with plain
 * arrays. They don't check permissions: the caller does.
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
 * Check profile values before they're saved: the nickname and display name must
 * not be in use by someone else.
 *
 * @param int   $user_id
 * @param array $values Field key => value, as posted by the account form, e.g. `user_nickname`.
 * @param array $args {
 *     @type bool $strict Also check that `user_email` is a valid email address. Default false.
 * }
 *
 * @return true|WP_Error
 */
function wpum_validate_profile_values( $user_id, $values, $args = array() ) {
	global $wpdb;

	$args   = wp_parse_args( $args, array(
		'strict' => false,
	) );
	$values = is_array( $values ) ? $values : array();

	if ( $args['strict'] && isset( $values['user_email'] ) && ! is_email( $values['user_email'] ) ) {
		return new WP_Error( 'wpum_profile_invalid_email', esc_html__( 'Please enter a valid email address.', 'wp-user-manager' ), array( 'status' => 400 ) );
	}

	if ( ! isset( $values['user_nickname'] ) ) {
		return true;
	}

	$user_displayname = isset( $values['user_displayname'] ) ? $values['user_displayname'] : null;

	$displayname = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE display_name = %s AND ID <> %d", $user_displayname, $user_id ) ); // phpcs:ignore
	$nickname    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname' AND meta.meta_value = %s AND users.ID <> %d", $values['user_nickname'], $user_id ) );  // phpcs:ignore

	if ( '1' === $displayname ) {
		return new WP_Error( 'displayname-unique-validation-error', esc_html__( 'This display name is already in use by someone else. Display names must be unique.', 'wp-user-manager' ) );
	}

	if ( '1' === $nickname ) {
		return new WP_Error( 'displayname-unique-validation-error', esc_html__( 'This nickname is already in use by someone else. Nicknames must be unique.', 'wp-user-manager' ) );
	}

	return true;
}

/**
 * Turn the display name option chosen in the account form into the name.
 *
 * @param WP_User $user
 * @param array   $values The profile values, for the first name, last name and nickname.
 * @param string  $choice `display_nickname`, `display_firstname`, `display_lastname`,
 *                        `display_firstlast` or `display_lastfirst`. Anything else
 *                        gives the username.
 *
 * @return string
 */
function wpum_get_display_name_from_choice( $user, $values, $choice ) {
	$name      = $user->user_login;
	$firstname = isset( $values['user_firstname'] ) ? $values['user_firstname'] : '';
	$lastname  = isset( $values['user_lastname'] ) ? $values['user_lastname'] : '';

	switch ( $choice ) {
		case 'display_nickname':
			$name = isset( $values['user_nickname'] ) ? $values['user_nickname'] : '';
			break;
		case 'display_firstname':
			$name = $firstname;
			break;
		case 'display_lastname':
			$name = $lastname;
			break;
		case 'display_firstlast':
			$name = $firstname . ' ' . $lastname;
			break;
		case 'display_lastfirst':
			$name = $lastname . ' ' . $firstname;
			break;
	}

	return $name;
}

/**
 * Save a user's profile values: first and last name, email, nickname, website,
 * description, display name, avatar and cover.
 *
 * Custom field values are saved by the Custom Fields addon on the
 * `wpum_after_user_update` action, which the account form fires around this.
 *
 * @param WP_User|int $user
 * @param array       $values Field key => value, e.g. `user_firstname`, `user_email`,
 *                            `user_displayname` (one of the display name choices).
 *                            Avatar and cover are arrays with `url` and `path`.
 * @param array       $args {
 *     @type string|false|null $current_avatar The avatar URL the form showed as uploaded, or false
 *                                             if it showed none. Default null: leave the avatar
 *                                             alone unless `user_avatar` is given.
 *     @type string|false|null $current_cover  The same for the cover image.
 *     @type bool              $partial        The form only had some of the profile fields, so a
 *                                             missing cover doesn't mean it was removed. Default false.
 *     @type string|null       $display_name   The display name to save when `user_displayname` is
 *                                             given, instead of working it out from the choice.
 * }
 *
 * @return int|WP_Error The user ID.
 */
function wpum_update_profile_values( $user, $values, $args = array() ) {
	$args = wp_parse_args( $args, array(
		'current_avatar' => null,
		'current_cover'  => null,
		'partial'        => false,
		'display_name'   => null,
	) );

	if ( ! $user instanceof WP_User ) {
		$user = get_userdata( (int) $user );
	}

	if ( ! $user ) {
		return new WP_Error( 'wpum_profile_user_not_found', esc_html__( 'The user does not exist.', 'wp-user-manager' ), array( 'status' => 404 ) );
	}

	$values = is_array( $values ) ? $values : array();

	// Collect all the data to update the user.
	$user_data = array(
		'ID' => $user->ID,
	);

	// Update first name and last name.
	if ( isset( $values['user_firstname'] ) ) {
		$user_data['first_name'] = wpum_sanitize_text( $values['user_firstname'] );
	}
	if ( isset( $values['user_lastname'] ) ) {
		$user_data['last_name'] = wpum_sanitize_text( $values['user_lastname'] );
	}

	// Update email address.
	if ( isset( $values['user_email'] ) ) {
		$user_data['user_email'] = wpum_sanitize_text( $values['user_email'] );
	}

	// Update nickname.
	if ( isset( $values['user_nickname'] ) ) {
		$user_data['nickname'] = wpum_sanitize_text( $values['user_nickname'] );
	}

	// Update website.
	if ( isset( $values['user_website'] ) ) {
		$user_data['user_url'] = wpum_sanitize_text( $values['user_website'] );
	}

	// Update description.
	if ( isset( $values['user_description'] ) ) {
		$user_data['description'] = wpum_sanitize_text( $values['user_description'] );
	}

	// Update displayed name.
	if ( isset( $values['user_displayname'] ) ) {
		$display_name              = null !== $args['display_name'] ? $args['display_name'] : wpum_get_display_name_from_choice( $user, $values, $values['user_displayname'] );
		$user_data['display_name'] = wpum_sanitize_text( $display_name );
	}

	// Now update the user.
	$updated_user_id = wp_update_user( $user_data );

	if ( is_wp_error( $updated_user_id ) ) {
		return $updated_user_id;
	}

	if ( wpum_get_option( 'custom_avatars' ) && ( null !== $args['current_avatar'] || isset( $values['user_avatar']['url'] ) ) ) {
		$currently_uploaded_file   = $args['current_avatar'] ? $args['current_avatar'] : false;
		$existing_avatar_file_path = get_user_meta( $updated_user_id, '_current_user_avatar_path', true );

		// Delete previous avatar if a new one has been uploaded.
		if ( $currently_uploaded_file && $existing_avatar_file_path && file_exists( $existing_avatar_file_path ) && isset( $values['user_avatar']['url'] ) && $values['user_avatar']['url'] !== $currently_uploaded_file ) {
			wp_delete_file( $existing_avatar_file_path );
		}

		// If no new avatar uploaded and existing file exists, delete it.
		if ( null !== $args['current_avatar'] && ! $currently_uploaded_file && file_exists( $existing_avatar_file_path ) ) {
			wp_delete_file( $existing_avatar_file_path );
			carbon_set_user_meta( $updated_user_id, 'current_user_avatar', false );
			delete_user_meta( $updated_user_id, '_current_user_avatar_path' );

			do_action( 'wpum_user_update_remove_avatar', $user->ID );
		}
		if ( isset( $values['user_avatar']['url'] ) && $currently_uploaded_file !== $values['user_avatar']['url'] ) {
			carbon_set_user_meta( $updated_user_id, 'current_user_avatar', $values['user_avatar']['url'] );
			update_user_meta( $updated_user_id, '_current_user_avatar_path', $values['user_avatar']['path'] );

			do_action( 'wpum_user_update_change_avatar', $user->ID, $values['user_avatar']['url'] );
		}
	}

	$currently_uploaded_cover = $args['current_cover'] ? $args['current_cover'] : false;
	$existing_cover_file_path = get_user_meta( $updated_user_id, '_user_cover_path', true );

	if ( isset( $values['user_cover']['url'] ) ) {
		if ( $currently_uploaded_cover && $existing_cover_file_path && file_exists( $existing_cover_file_path ) && isset( $values['user_cover']['url'] ) && $values['user_cover']['url'] !== $currently_uploaded_cover ) {
			wp_delete_file( $existing_cover_file_path );
		}
		if ( $currently_uploaded_cover !== $values['user_cover']['url'] ) {
			carbon_set_user_meta( $updated_user_id, 'user_cover', $values['user_cover']['url'] );
			update_user_meta( $updated_user_id, '_user_cover_path', $values['user_cover']['path'] );

			do_action( 'wpum_user_update_change_cover', $user->ID, $values['user_cover']['url'] );
		}
	}

	if ( null !== $args['current_cover'] && ! $args['partial'] && ! $currently_uploaded_cover && file_exists( $existing_cover_file_path ) && ! isset( $values['user_cover']['url'] ) ) {
		wp_delete_file( $existing_cover_file_path );
		carbon_set_user_meta( $updated_user_id, 'user_cover', false );
		delete_user_meta( $updated_user_id, '_user_cover_path' );

		do_action( 'wpum_user_update_remove_cover', $user->ID );
	}

	return $updated_user_id;
}
