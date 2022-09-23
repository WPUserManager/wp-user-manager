<?php
/**
 * Handles the WPUM account shared functions.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * WPUM_Form_Account
 */
trait WPUM_Form_Account {

	/**
	 * @param \WP_User $user
	 * @param array    $values
	 * @param bool     $partial_form
	 *
	 * @return int|WP_Error
	 * @throws Exception
	 */
	protected function update_account_values( $user, $values, $partial_form = false ) {
		// Collect all the data to update the user.
		$user_data = array(
			'ID' => $user->ID,
		);

		// Update first name and last name.
		if ( isset( $values['account']['user_firstname'] ) ) {
			$user_data['first_name'] = $values['account']['user_firstname'];
		}
		if ( isset( $values['account']['user_lastname'] ) ) {
			$user_data['last_name'] = $values['account']['user_lastname'];
		}

		// Update email address.
		if ( isset( $values['account']['user_email'] ) ) {
			$user_data['user_email'] = $values['account']['user_email'];
		}

		// Update nickname.
		if ( isset( $values['account']['user_nickname'] ) ) {
			$user_data['nickname'] = $values['account']['user_nickname'];
		}

		// Update website.
		if ( isset( $values['account']['user_website'] ) ) {
			$user_data['user_url'] = $values['account']['user_website'];
		}

		// Update description.
		if ( isset( $values['account']['user_description'] ) ) {
			$user_data['description'] = $values['account']['user_description'];
		}

		// Update displayed name.
		if ( isset( $values['account']['user_displayname'] ) ) {
			$user_data['display_name'] = $this->parse_displayname( $values['account'], $values['account']['user_displayname'] );
		}

		// Now update the user.
		$updated_user_id = wp_update_user( $user_data );

		if ( is_wp_error( $updated_user_id ) ) {
			throw new Exception( $updated_user_id->get_error_message() );
		}

		if ( wpum_get_option( 'custom_avatars' ) ) {
			$current_uploaded_avatar = filter_input( INPUT_POST, 'current_user_avatar' );
			$currently_uploaded_file = $current_uploaded_avatar ? esc_url_raw( $current_uploaded_avatar ) : false;

			$existing_avatar_file_path = get_user_meta( $updated_user_id, '_current_user_avatar_path', true );
			if ( $currently_uploaded_file && $existing_avatar_file_path && isset( $values['account']['user_avatar']['url'] ) && $values['account']['user_avatar']['url'] !== $currently_uploaded_file ) {
				wp_delete_file( $existing_avatar_file_path );
			}
			if ( ! $currently_uploaded_file && file_exists( $existing_avatar_file_path ) ) {
				wp_delete_file( $existing_avatar_file_path );
				carbon_set_user_meta( $updated_user_id, 'current_user_avatar', false );
				delete_user_meta( $updated_user_id, '_current_user_avatar_path' );

				do_action( 'wpum_user_update_remove_avatar', $user->ID );
			}
			if ( isset( $values['account']['user_avatar']['url'] ) && $currently_uploaded_file !== $values['account']['user_avatar']['url'] ) {
				carbon_set_user_meta( $updated_user_id, 'current_user_avatar', $values['account']['user_avatar']['url'] );
				update_user_meta( $updated_user_id, '_current_user_avatar_path', $values['account']['user_avatar']['path'] );

				do_action( 'wpum_user_update_change_avatar', $user->ID, $values['account']['user_avatar']['url'] );
			}
		}

		$current_uploaded_cover   = filter_input( INPUT_POST, 'current_user_cover' );
		$currently_uploaded_cover = $current_uploaded_cover ? esc_url_raw( $current_uploaded_cover ) : false;
		$existing_cover_file_path = get_user_meta( $updated_user_id, '_user_cover_path', true );

		if ( isset( $values['account']['user_cover']['url'] ) ) {
			if ( $currently_uploaded_cover && $existing_cover_file_path && isset( $values['account']['user_cover']['url'] ) && $values['account']['user_cover']['url'] !== $currently_uploaded_cover ) {
				wp_delete_file( $existing_cover_file_path );
			}
			if ( $currently_uploaded_cover !== $values['account']['user_cover']['url'] ) {
				carbon_set_user_meta( $updated_user_id, 'user_cover', $values['account']['user_cover']['url'] );
				update_user_meta( $updated_user_id, '_user_cover_path', $values['account']['user_cover']['path'] );

				do_action( 'wpum_user_update_change_cover', $user->ID, $values['account']['user_cover']['url'] );
			}
		}

		if ( ! $partial_form && ! $currently_uploaded_cover && file_exists( $existing_cover_file_path ) && ! isset( $values['account']['user_cover']['url'] ) ) {
			wp_delete_file( $existing_cover_file_path );
			carbon_set_user_meta( $updated_user_id, 'user_cover', false );
			delete_user_meta( $updated_user_id, '_user_cover_path' );

			do_action( 'wpum_user_update_remove_cover', $user->ID );
		}

		return $updated_user_id;
	}
	/**
	 * Retrieve the value of a given field for the currently logged in user.
	 *
	 * @param WP_User    $user
	 * @param WPUM_Field $field
	 *
	 * @return mixed
	 */
	protected function get_user_field_value( $user, $field ) {

		$value = false;

		if ( ! empty( $field->get_primary_id() ) ) {

			switch ( $field->get_primary_id() ) {
				case 'user_firstname':
					$value = esc_html( $user->user_firstname );
					break;
				case 'user_lastname':
					$value = esc_html( $user->user_lastname );
					break;
				case 'user_email':
					$value = esc_html( $user->user_email );
					break;
				case 'user_nickname':
					$value = esc_html( get_user_meta( $user->ID, 'nickname', true ) );
					break;
				case 'user_website':
					$value = esc_html( $user->user_url );
					break;
				case 'user_description':
					$value = esc_textarea( get_user_meta( $user->ID, 'description', true ) );
					break;
				case 'user_displayname':
					$value = $this->get_selected_displayname( $user );
					break;
				case 'user_avatar':
					$value = \WPUM\carbon_get_user_meta( $user->ID, 'current_user_avatar' );
					break;
				case 'user_cover':
					$value = \WPUM\carbon_get_user_meta( $user->ID, 'user_cover' );
					break;
			}
		} elseif ( strpos( $field->get_meta( 'user_meta_key' ), 'wpum_' ) === 0 ) {

			$value = \WPUM\carbon_get_user_meta( $user->ID, $field->get_meta( 'user_meta_key' ) );

		} else {

			$value = esc_html( get_user_meta( $user->ID, $field->get_meta( 'user_meta_key' ), true ) );

		}

		return apply_filters( 'wpum_custom_field_value', $value, $field, $user->ID );
	}

	/**
	 * Retrieve the option currently selected for the display name setting.
	 *
	 * @param \WP_User $user
	 *
	 * @return string
	 */
	protected function get_selected_displayname( $user ) {
		$selected_name  = $user->display_name;
		$user_login     = $user->user_login;
		$nickname       = $user->nickname;
		$first_name     = $user->first_name;
		$last_name      = $user->last_name;
		$firstlast      = $user->first_name . ' ' . $user->last_name;
		$lastfirst      = $user->last_name . ' ' . $user->first_name;
		$selected_value = $user_login;

		switch ( $selected_name ) {
			case $nickname:
				$selected_value = 'display_nickname';
				break;
			case $first_name:
				$selected_value = 'display_firstname';
				break;
			case $last_name:
				$selected_value = 'display_lastname';
				break;
			case $firstlast:
				$selected_value = 'display_firstlast';
				break;
			case $lastfirst:
				$selected_value = 'display_lastfirst';
				break;
			default:
				$selected_value = $user_login;
				break;
		}

		return $selected_value;
	}
}
