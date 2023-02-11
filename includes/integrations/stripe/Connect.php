<?php

namespace WPUserManager\WPUMStripe;

class Connect {

	public function init() {
		add_action( 'admin_init', array( $this, 'complete' ) );
	}

	public function get_base_url() {
		return apply_filters( 'wpum_stripe_connect_base_url', 'https://connect.wpusermanager.com' );
	}

	public function get_gateway_mode() {
		return wpum_get_option( 'stripe_gateway_mode', 'test' );
	}

	public function is_test_mode() {
		return 'test' === $this->get_gateway_mode();
	}

	public function get_stripe_key() {
		$prefix = $this->is_test_mode() ? 'test_' : 'live_';

		return wpum_get_option( $prefix . 'stripe_publishable_key' );
	}

	public function get_stripe_secret() {
		$prefix = $this->is_test_mode() ? 'test_' : 'live_';

		return wpum_get_option( $prefix . 'stripe_secret_key' );
	}

	public function get_stripe_webhook_secret() {
		$prefix = $this->is_test_mode() ? 'test_' : 'live_';

		return wpum_get_option( $prefix . 'stripe_webhook_secret' );
	}

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

	protected function get_site_url() {
		$return_url = add_query_arg( array(
			'page' => 'wpum-settings',
		), admin_url( 'users.php' ) );

		return apply_filters( 'wpum_stripe_connect_return_url', $return_url );
	}

	protected function get_state() {
		$state = array(
			'live_mode' => (int) ! $this->is_test_mode(),
			'site_id'   => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 10, wp_rand(), STR_PAD_BOTH ),
			'site_url'  => $this->get_site_url(),
		);

		return base64_encode( serialize( $state ) );
	}

	public function connect_url() {
		$stripe_connect_url = add_query_arg( array(
			'state' => $this->get_state(),
		), $this->get_base_url() );

		return apply_filters( 'wpum_stripe_connect_url', $stripe_connect_url );
	}

	public function disconnect_url() {
		$stripe_disconnect_url = add_query_arg(
			array(
				'page'       => 'wpum-settings',
				'disconnect' => true,
				'mode' => $this->is_test_mode() ? 'test' : 'live',
			),
			admin_url( 'users.php' )
		);

		$stripe_disconnect_url = wp_nonce_url( $stripe_disconnect_url, 'wpum-stripe-connect-disconnect' );

		$stripe_disconnect_url .= '#/stripe';

		return $stripe_disconnect_url;
	}

	public function complete() {
		if ( ! isset( $_GET['page'] ) || 'wpum-settings' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_GET['action'] ) || 'stripe_connect' !== $_GET['action'] ) {
			return;
		}

		if ( ! isset( $_GET['state'] ) ) {
			return;
		}

		if ( headers_sent() ) {
			return;
		}

		$wpum_credentials_url = add_query_arg( array(
			'state' => sanitize_text_field( $_GET['state'] ),
		), $this->get_base_url() . '/creds' );

		$response = wp_remote_get( esc_url_raw( $wpum_credentials_url ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$message = '<p>' . sprintf( /* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */ __( 'There was an error getting your Stripe credentials. Please %1$stry again%2$s. If you continue to have this problem, please contact support.', 'wp-user-manager' ), '<a href="' . esc_url( $this->get_site_url() . '#/stripe' ) . '" target="_blank" rel="noopener noreferrer">', '</a>' ) . '</p>';
			wp_die( $message );
		}

		$data = json_decode( $response['body'], true );

		if ( $this->is_test_mode() ) {
			wpum_update_option( 'test_stripe_publishable_key', sanitize_text_field( $data['publishable_key'] ) );
			wpum_update_option( 'test_stripe_secret_key', sanitize_text_field( $data['secret_key'] ) );
		} else {
			wpum_update_option( 'live_stripe_publishable_key', sanitize_text_field( $data['publishable_key'] ) );
			wpum_update_option( 'live_stripe_secret_key', sanitize_text_field( $data['secret_key'] ) );
		}

		delete_transient( 'wpum_stripe_products' );

		wpum_update_option( 'stripe_connect_account_id', sanitize_text_field( $data['stripe_user_id'] ) );
		wp_redirect( $this->get_site_url() . '/#stripe' );
		exit;
	}
}
