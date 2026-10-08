<?php
/**
 * Handles the Stripe Connect
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUserManager\Stripe\Controllers\Products;

/**
 * Connect
 */
class Connect {

	/**
	 * Init
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'complete' ) );
	}

	/**
	 * @return string
	 */
	public function get_base_url() {
		return apply_filters( 'wpum_stripe_connect_base_url', 'https://connect.wpusermanager.com' );
	}

	/**
	 * @return string
	 */
	public function get_gateway_mode() {
		return wpum_get_option( 'stripe_gateway_mode', 'test' );
	}

	/**
	 * @return bool
	 */
	public function is_test_mode() {
		return 'test' === $this->get_gateway_mode();
	}

	/**
	 * @param null|string $prefix
	 *
	 * @return string
	 */
	public function get_stripe_key( $prefix = null ) {
		if ( empty( $prefix ) ) {
			$prefix = $this->is_test_mode() ? 'test' : 'live';
		}

		return wpum_get_option( $prefix . '_stripe_publishable_key' );
	}

	/**
	 * @param null|string $prefix
	 *
	 * @return string
	 */
	public function get_stripe_secret( $prefix = null ) {
		if ( empty( $prefix ) ) {
			$prefix = $this->is_test_mode() ? 'test' : 'live';
		}

		return wpum_get_option( $prefix . '_stripe_secret_key' );
	}

	/**
	 * @return string
	 */
	public function get_stripe_webhook_secret() {
		$prefix = $this->is_test_mode() ? 'test_' : 'live_';

		return wpum_get_option( $prefix . 'stripe_webhook_secret' );
	}

	/**
	 * @return bool
	 */
	public function is_connected() {
		$account_id = wpum_get_option( 'stripe_connect_account_id' );
		if ( empty( $account_id ) ) {
			return false;
		}

		if ( empty( $this->get_stripe_key() ) || empty( $this->get_stripe_secret() ) ) {
			return false;
		}

		if ( empty( $this->get_stripe_webhook_secret() ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return string
	 */
	protected function get_site_url() {
		$return_url = add_query_arg( array(
			'page' => 'wpum-settings',
		), admin_url( 'users.php' ) );

		return apply_filters( 'wpum_stripe_connect_return_url', $return_url );
	}

	/**
	 * The state for a Connect button. The current user's unused state for this
	 * mode is reused, since the buttons are built on every settings load,
	 * including every REST request.
	 *
	 * @param false $test_mode
	 *
	 * @return string
	 */
	protected function get_state( $test_mode = false ) {
		$mode   = $test_mode ? 'test' : 'live';
		$issued = $this->get_issued_states();
		if ( isset( $issued[ $mode ]['state'], $issued[ $mode ]['time'] ) && $issued[ $mode ]['time'] > time() - 12 * HOUR_IN_SECONDS ) {
			return $issued[ $mode ]['state'];
		}

		$state = array(
			'test_mode' => (int) $test_mode,
			'site_id'   => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 10, wp_rand(), STR_PAD_BOTH ),
			'site_url'  => $this->get_site_url(),
		);

		$state = base64_encode( serialize( $state ) ); // phpcs:ignore

		$this->remember_state( $mode, $state );

		return $state;
	}

	/**
	 * Transient key holding the connection states issued to the current user.
	 *
	 * @return string
	 */
	protected function get_states_transient_key() {
		return 'wpum_stripe_connect_states_' . get_current_user_id();
	}

	/**
	 * The states issued to the current user, by mode.
	 *
	 * @return array
	 */
	protected function get_issued_states() {
		$states = get_transient( $this->get_states_transient_key() );
		if ( ! is_array( $states ) ) {
			return array();
		}

		// Before 2.9.23 this held a list of states; only the by-mode entries are reused.
		return array_intersect_key( $states, array_flip( array( 'test', 'live' ) ) );
	}

	/**
	 * Record a state issued to the current user so the callback can be tied to it.
	 *
	 * @param string $mode  'test' or 'live'.
	 * @param string $state
	 */
	protected function remember_state( $mode, $state ) {
		if ( ! get_current_user_id() ) {
			return;
		}

		$states          = $this->get_issued_states();
		$states[ $mode ] = array(
			'state' => $state,
			'time'  => time(),
		);

		set_transient( $this->get_states_transient_key(), $states, DAY_IN_SECONDS );
	}

	/**
	 * Check a callback state was issued to the current user, and consume it.
	 *
	 * @param string $state
	 *
	 * @return bool
	 */
	protected function consume_state( $state ) {
		$states = get_transient( $this->get_states_transient_key() );
		if ( ! is_array( $states ) ) {
			return false;
		}

		// A '+' in the base64 state can arrive as a space once URL-decoded, and
		// add_query_arg() strips a trailing '=' from the Connect URL, so ignore padding.
		$state = rtrim( str_replace( ' ', '+', $state ), '=' );

		foreach ( $states as $issued ) {
			// Before 2.9.23 each entry was the state itself.
			$issued = is_array( $issued ) && isset( $issued['state'] ) ? $issued['state'] : $issued;

			if ( is_string( $issued ) && hash_equals( rtrim( $issued, '=' ), $state ) ) {
				delete_transient( $this->get_states_transient_key() );

				return true;
			}
		}

		return false;
	}

	/**
	 * @param false $test_mode
	 *
	 * @return string
	 */
	public function connect_url( $test_mode = false ) {
		$stripe_connect_url = add_query_arg( array(
			'state' => $this->get_state( $test_mode ),
		), $this->get_base_url() );

		return apply_filters( 'wpum_stripe_connect_url', $stripe_connect_url );
	}

	/**
	 * @param string $mode
	 *
	 * @return string
	 */
	public function disconnect_url( $mode ) {
		$stripe_disconnect_url = add_query_arg(
			array(
				'page'       => 'wpum-settings',
				'disconnect' => true,
				'mode'       => $mode,
			),
			admin_url( 'users.php' )
		);

		$stripe_disconnect_url = wp_nonce_url( $stripe_disconnect_url, 'wpum-stripe-connect-disconnect' );

		$stripe_disconnect_url .= '#/stripe';

		return $stripe_disconnect_url;
	}

	/**
	 * Complete connection
	 */
	public function complete() {
		$page = filter_input( INPUT_GET, 'page', FILTER_UNSAFE_RAW );
		$page = sanitize_text_field( $page );
		if ( empty( $page ) || 'wpum-settings' !== $page ) {
			return;
		}

		$action = filter_input( INPUT_GET, 'action', FILTER_UNSAFE_RAW );
		$action = sanitize_text_field( $action );
		if ( empty( $action ) || 'stripe_connect' !== $action ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$state = filter_input( INPUT_GET, 'state', FILTER_UNSAFE_RAW );
		$state = sanitize_text_field( $state );
		if ( empty( $state ) ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		if ( ! $this->consume_state( $state ) ) {
			/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
			$message = '<p>' . sprintf( __( 'This Stripe connection request has expired or was not started from this site. Please %1$sconnect again%2$s.', 'wp-user-manager' ), '<a href="' . esc_url( $this->get_site_url() . '#/stripe' ) . '">', '</a>' ) . '</p>';
			wp_die( $message );  // phpcs:ignore
		}

		$wpum_credentials_url = add_query_arg( array(
			'state' => sanitize_text_field( $state ),
		), $this->get_base_url() . '/creds' );

		$response = wp_remote_get( esc_url_raw( $wpum_credentials_url ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
			$message = '<p>' . sprintf( __( 'There was an error getting your Stripe credentials. Please %1$stry again%2$s. If you continue to have this problem, please contact support.', 'wp-user-manager' ), '<a href="' . esc_url( $this->get_site_url() . '#/stripe' ) . '" target="_blank" rel="noopener noreferrer">', '</a>' ) . '</p>';
			wp_die( $message );  // phpcs:ignore
		}

		$data = json_decode( $response['body'], true );

		$gateway_mode = 'test';
		if ( $data['test_mode'] ) {
			wpum_update_option( 'test_stripe_publishable_key', sanitize_text_field( $data['publishable_key'] ) );
			wpum_update_option( 'test_stripe_secret_key', sanitize_text_field( $data['secret_key'] ) );
		} else {
			$gateway_mode = 'live';
			wpum_update_option( 'live_stripe_publishable_key', sanitize_text_field( $data['publishable_key'] ) );
			wpum_update_option( 'live_stripe_secret_key', sanitize_text_field( $data['secret_key'] ) );
		}

		wpum_update_option( 'stripe_gateway_mode', $gateway_mode );

		Products::forget( $gateway_mode );

		wpum_update_option( 'stripe_connect_account_id', sanitize_text_field( $data['stripe_user_id'] ) );
		wp_safe_redirect( $this->get_site_url() . '/#stripe' );
		exit;
	}
}
