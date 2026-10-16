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
		$current_uploaded_avatar = filter_input( INPUT_POST, 'current_user_avatar' );
		$current_uploaded_cover  = filter_input( INPUT_POST, 'current_user_cover' );

		$args = array(
			'current_avatar' => $current_uploaded_avatar ? esc_url_raw( $current_uploaded_avatar ) : false,
			'current_cover'  => $current_uploaded_cover ? esc_url_raw( $current_uploaded_cover ) : false,
			'partial'        => $partial_form,
		);

		// A form that works out the display name itself still does.
		if ( isset( $values['account']['user_displayname'] ) && method_exists( $this, 'parse_displayname' ) ) {
			$args['display_name'] = $this->parse_displayname( $values['account'], $values['account']['user_displayname'] );
		}

		$updated_user_id = wpum_update_profile_values( $user, isset( $values['account'] ) ? $values['account'] : array(), $args );

		if ( is_wp_error( $updated_user_id ) ) {
			throw new Exception( esc_html( $updated_user_id->get_error_message() ) );
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
