<?php
/**
 * Handles all the avatar related functionalities of WPUM in the backend.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Avatar handler class.
 */
class WPUM_Avatars {

	/**
	 * The user ID.
	 *
	 * @var string
	 */
	private $user_id;

	/**
	 * Get things started.
	 */
	public function __construct() {

		global $pagenow;

		if ( ! wpum_get_option( 'custom_avatars' ) ) {
			return;
		}

		add_action( 'carbon_fields_register_fields', [ $this, 'avatar_field' ] );
		add_filter( 'get_avatar_url', [ $this, 'set_avatar_url' ], 10, 3 );

	}

	/**
	 * Retrieve the correct user ID based on whichever page we're viewing.
	 *
	 * @return int
	 */
	private function get_user_id( $id_or_email ) {

		// Default
		$retval = 0;

		if ( is_numeric( $id_or_email ) ) {

			$retval = $id_or_email;

		} elseif ( is_string( $id_or_email ) ) {

			$user_by = is_email( $id_or_email ) ? 'email' : 'login';

			$user = get_user_by( $user_by, $id_or_email );

			if ( ! empty( $user ) ) {
				$retval = $user->ID;
			}
		} elseif ( $id_or_email instanceof WP_User ) {
			$user = $id_or_email->ID;
		} elseif ( $id_or_email instanceof WP_Post ) {
			$retval = $id_or_email->post_author;
		} elseif ( $id_or_email instanceof WP_Comment ) {
			if ( ! empty( $id_or_email->user_id ) ) {
				$retval = $id_or_email->user_id;
			}
		}

		return (int) apply_filters( 'wpum_avatars_get_user_id', (int) $retval, $id_or_email );

	}

	/**
	 * Add avatar field in the WordPress backend.
	 *
	 * @return void
	 */
	public function avatar_field() {
		Container::make( 'user_meta', esc_html__( 'Avatar', 'wp-user-manager' ) )
			->set_datastore( new WPUM_User_Meta_Custom_Datastore() )
			->add_fields(
				array(
					Field::make( 'image', 'current_user_avatar', esc_html__( 'Custom user avatar', 'wp-user-manager' ) )
						->set_value_type( 'url' ),
					Field::make( 'image', 'user_cover', esc_html__( 'Custom profile cover image', 'wp-user-manager' ) )
						->set_value_type( 'url' ),
				)
			);
	}

	/**
	 * Override WordPress default avatar URL with the custom one.
	 *
	 * @param string $url
	 * @param mixed $id_or_email
	 * @param array $args
	 * @return void
	 */
	public function set_avatar_url( $url, $id_or_email, $args ) {

		// Bail if forcing default.
		if ( ! empty( $args['force_default'] ) ) {
			return $url;
		}

		// Bail if explicitly an md5'd Gravatar url.
		if ( is_string( $id_or_email ) && strpos( $id_or_email, '@md5.gravatar.com' ) ) {
			return $url;
		}

		$custom_avatar = carbon_get_user_meta( $this->get_user_id( $id_or_email ), 'current_user_avatar' );

		if ( $custom_avatar && $custom_avatar !== 'false' ) {
			$url = $custom_avatar;
		}

		return apply_filters( 'wpum_get_avatar_url', $url, $id_or_email );

	}

}

new WPUM_Avatars;
