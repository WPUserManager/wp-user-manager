<?php
/**
 * Handles the Stripe Connect
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

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
	 * @param false $test_mode
	 *
	 * @return string
	 */
	protected function get_state( $test_mode = false ) {
		$state = array(
			'test_mode' => (int) $test_mode,
			'site_id'   => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 10, wp_rand(), STR_PAD_BOTH ),
			'site_url'  => $this->get_site_url(),
		);

		return base64_encode( serialize( $state ) ); // phpcs:ignore
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
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );
		if ( empty( $page ) || 'wpum-settings' !== $page ) {
			return;
		}

		$action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
		if ( empty( $action ) || 'stripe_connect' !== $action ) {
			return;
		}

		$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_STRING );
		if ( empty( $state ) ) {
			return;
		}

		if ( headers_sent() ) {
			return;
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

		delete_transient( 'wpum_' . $gateway_mode . '_stripe_products' );

		wpum_update_option( 'stripe_connect_account_id', sanitize_text_field( $data['stripe_user_id'] ) );
		wp_safe_redirect( $this->get_site_url() . '/#stripe' );
		exit;
	}
}
