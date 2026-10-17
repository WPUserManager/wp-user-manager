<?php
/**
 * Disconnecting Stripe tells the Connect service, signed with the mode's secret
 * key, so it deletes the connection and revokes its access to the account. The
 * site forgets its keys whatever the service answers.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ConnectDisconnectTest extends WPUMTestCase {

	const SECRET = 'sk_test_disconnect_secret';

	/**
	 * Requests made to the Connect service.
	 *
	 * @var array
	 */
	protected $requests = array();

	/**
	 * What the fake Connect service answers.
	 *
	 * @var array|\WP_Error
	 */
	protected $response;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Connect' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		wpum_update_option( 'stripe_connect_account_id', 'acct_disconnect' );
		foreach ( array( 'test', 'live' ) as $mode ) {
			wpum_update_option( $mode . '_stripe_publishable_key', 'pk_' . $mode . '_x' );
			wpum_update_option( $mode . '_stripe_secret_key', 'test' === $mode ? self::SECRET : 'sk_live_disconnect_secret' );
			wpum_update_option( $mode . '_stripe_webhook_secret', 'whsec_' . $mode );
		}

		$this->requests = array();
		$this->response = array(
			'headers'  => array(),
			'body'     => '{"disconnected":true,"deauthorized":true}',
			'response' => array( 'code' => 200, 'message' => 'OK' ),
			'cookies'  => array(),
		);

		add_filter( 'pre_http_request', array( $this, 'fake_connect_service' ), 10, 3 );
	}

	public function _tearDown() {
		remove_filter( 'pre_http_request', array( $this, 'fake_connect_service' ), 10 );

		wpum_delete_option( 'stripe_connect_account_id' );
		foreach ( array( 'test', 'live' ) as $mode ) {
			wpum_delete_option( $mode . '_stripe_publishable_key' );
			wpum_delete_option( $mode . '_stripe_secret_key' );
			wpum_delete_option( $mode . '_stripe_webhook_secret' );
		}

		parent::_tearDown();
	}

	public function fake_connect_service( $pre, $args, $url ) {
		$this->requests[] = array(
			'url'  => $url,
			'args' => $args,
		);

		return $this->response;
	}

	protected function settings() {
		return new \WPUserManager\Stripe\Settings( new \WPUserManager\Stripe\Connect() );
	}

	public function test_disconnect_sends_a_signed_request_to_the_connect_service() {
		$this->settings()->disconnect( 'test' );

		$this->assertCount( 1, $this->requests );
		$request = $this->requests[0];
		$body    = $request['args']['body'];

		$this->assertSame( 'https://connect.wpusermanager.com/disconnect', $request['url'] );
		$this->assertSame( 'POST', $request['args']['method'] );
		$this->assertSame( 'acct_disconnect', $body['stripe_account_id'] );
		$this->assertSame( '1', $body['test_mode'] );
		$this->assertEqualsWithDelta( time(), (int) $body['timestamp'], 5 );

		$expected = hash_hmac( 'sha256', \WPUserManager\Stripe\Billing::canonicalConnectRequest( $body ), self::SECRET );
		$this->assertSame( $expected, $body['signature'] );
		$this->assertNotContains( self::SECRET, $body, 'The secret key must never be sent' );
	}

	public function test_live_disconnect_signs_with_the_live_key() {
		$this->settings()->disconnect( 'live' );

		$body = $this->requests[0]['args']['body'];
		$this->assertSame( '0', $body['test_mode'] );
		$this->assertSame( hash_hmac( 'sha256', \WPUserManager\Stripe\Billing::canonicalConnectRequest( $body ), 'sk_live_disconnect_secret' ), $body['signature'] );
	}

	public function test_disconnect_forgets_the_modes_keys() {
		$this->settings()->disconnect( 'test' );

		$this->assertEmpty( wpum_get_option( 'test_stripe_publishable_key' ) );
		$this->assertEmpty( wpum_get_option( 'test_stripe_secret_key' ) );
		$this->assertEmpty( wpum_get_option( 'test_stripe_webhook_secret' ) );
		$this->assertSame( 'sk_live_disconnect_secret', wpum_get_option( 'live_stripe_secret_key' ), 'The other mode stays connected' );
	}

	public function test_disconnect_forgets_the_keys_when_the_connect_service_fails() {
		foreach ( array( new \WP_Error( 'http_request_failed', 'Connection timed out' ), array(
			'headers'  => array(),
			'body'     => '{"error":"invalid_signature"}',
			'response' => array( 'code' => 403, 'message' => 'Forbidden' ),
			'cookies'  => array(),
		) ) as $response ) {
			wpum_update_option( 'test_stripe_secret_key', self::SECRET );
			$this->response = $response;

			$this->settings()->disconnect( 'test' );

			$this->assertEmpty( wpum_get_option( 'test_stripe_secret_key' ) );
		}

		$this->assertCount( 2, $this->requests );
	}

	public function test_disconnect_without_a_connection_sends_nothing() {
		wpum_delete_option( 'test_stripe_secret_key' );
		$this->settings()->disconnect( 'test' );

		wpum_delete_option( 'stripe_connect_account_id' );
		$this->settings()->disconnect( 'live' );

		$this->assertSame( array(), $this->requests );
		$this->assertEmpty( wpum_get_option( 'live_stripe_secret_key' ) );
	}

	public function test_disconnect_from_service_only_for_test_or_live() {
		$this->assertFalse( ( new \WPUserManager\Stripe\Connect() )->disconnect_from_service( 'staging' ) );
		$this->assertSame( array(), $this->requests );
	}
}
