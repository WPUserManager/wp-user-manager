<?php
/**
 * Handles all the avatar related functionalities of WPUM in the backend.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

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

		if ( wpum_get_option( 'custom_avatars' ) ) {
			add_action( 'carbon_fields_register_fields', array( $this, 'avatar_field' ) );
			add_filter( 'get_avatar_url', array( $this, 'set_avatar_url' ), 10, 3 );
		}

		if ( ! wpum_get_option( 'disable_profile_cover' ) ) {
			add_action( 'carbon_fields_register_fields', array( $this, 'cover_field' ) );
		}

		if ( false !== wpum_get_option( 'default_avatar' ) ) {
			add_image_size( 'wpum-avatar', 100, 100, true );
			add_filter( 'get_avatar_url', array( $this, 'set_default_avatar' ), 10, 3 );
			add_filter( 'avatar_defaults', function ( $defaults ) {
				$defaults = array();

				$defaults['wpum'] = __( 'Set with by WP User Manager.', 'wp-user-manager' ) . sprintf( ' <a href="%s">%s</a>', admin_url( 'users.php?page=wpum-settings#/profiles' ), __( 'Edit' ) );

				return $defaults;
			} );
			add_filter( 'pre_option_avatar_default', function ( $default ) {
				return 'wpum';
			} );

			add_filter( 'pre_update_option_wpum_settings', function ( $value, $old_value ) {
				if ( isset( $value['default_avatar'] ) && ( empty( $old_value['default_avatar'] ) || $old_value['default_avatar'] !== $value['default_avatar'] ) ) {
					$url           = $value['default_avatar'];
					$attachment_id = attachment_url_to_postid( $url );
					if ( $attachment_id ) {
						$url = wp_get_attachment_image_url( $attachment_id, 'wpum-avatar' );
					}
					$value['default_avatar_url'] = $url;
				}

				return $value;
			}, 10, 2 );
		}
	}

	/**
	 * Retrieve the correct user ID based on whichever page we're viewing.
	 *
	 * @param string|int $id_or_email
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
				)
			);
	}

	/**
	 * Add the profile cover field in admin panel.
	 *
	 * @return void
	 */
	public function cover_field() {
		Container::make( 'user_meta', esc_html__( 'Cover', 'wp-user-manager' ) )
			->set_datastore( new WPUM_User_Meta_Custom_Datastore() )
			->add_fields(
				array(
					Field::make( 'image', 'user_cover', esc_html__( 'Custom profile cover image', 'wp-user-manager' ) )
						->set_value_type( 'url' ),
				)
			);
	}

	/**
	 * Override WordPress default avatar URL with the custom one.
	 *
	 * @param string $url
	 * @param mixed  $id_or_email
	 * @param array  $args
	 *
	 * @return string
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

		if ( ! $this->carbon_fields_loaded() ) {
			return $url;
		}

		$custom_avatar = \WPUM\carbon_get_user_meta( $this->get_user_id( $id_or_email ), 'current_user_avatar' );

		if ( $custom_avatar && 'false' !== $custom_avatar ) {
			$url = $custom_avatar;
		}

		return apply_filters( 'wpum_get_avatar_url', $url, $id_or_email, $args );
	}

	/**
	 * Check Carbon Fields has been properly loaded before we use a function.
	 *
	 * @return bool
	 */
	protected function carbon_fields_loaded() {
		$register_action   = 'carbon_fields_register_fields';
		$registered_action = 'carbon_fields_fields_registered';
		if ( ! doing_action( $register_action ) && ! doing_action( $registered_action ) && did_action( $registered_action ) === 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * @param string $url
	 * @param mixed  $id_or_email
	 * @param array  $args
	 *
	 * @return array|mixed|string|string[]
	 */
	public function set_default_avatar( $url, $id_or_email, $args ) {
		global $pagenow;
		if ( is_admin() && ( ! isset( $pagenow ) || 'options-discussion.php' !== $pagenow ) ) {
			return $url;
		}

		$cache_key = 'wpum_default_avatar_' . $id_or_email;

		$default_url = wpum_get_option( 'default_avatar_url' );
		if ( empty( $default_url ) ) {
			$default_url = wpum_get_option( 'default_avatar' );
		}

		$cached_url = get_transient( $cache_key );

		if ( $cached_url ) {
			return $default_url;
		}

		$url      = str_replace( 'd=' . $args['default'], 'd=404', $url );
		$response = wp_remote_head( $url );
		if ( 404 === wp_remote_retrieve_response_code( $response ) ) {
			set_transient( $cache_key, 1, DAY_IN_SECONDS );

			return $url;
		}

		return $url;
	}

}

new WPUM_Avatars();
