<?php
/**
 * Checkout requests to the Stripe Connect service are signed with the
 * connected account's secret key, which is never sent.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

if ( class_exists( 'WPUserManager\Stripe\Controllers\Products' ) && ! class_exists( 'Connect_Signature_Stripe_Products' ) ) {
	/**
	 * Products controller backed by a fixed catalogue instead of the Stripe API.
	 */
	class Connect_Signature_Stripe_Products extends \WPUserManager\Stripe\Controllers\Products {

		public function __construct() {
			$this->gateway_mode = 'test';
			$this->products     = $this->all();
		}

		public function all( $force = false ) {
			return array(
				'prod_premium' => array(
					'name'   => 'Premium',
					'prices' => array(
						'price_premium' => array( 'type' => 'one_time', 'unit_amount' => 10000, 'currency' => 'usd' ),
						'price_monthly' => array( 'type' => 'recurring', 'unit_amount' => 500, 'currency' => 'usd' ),
					),
				),
			);
		}
	}
}

class ConnectCheckoutSignatureTest extends WPUMTestCase {

	const SECRET = 'sk_test_connect_signature_secret';

	/**
	 * URLs requested from the Connect service.
	 *
	 * @var array
	 */
	protected $requests = array();

	/**
	 * The fake Connect service response.
	 *
	 * @var array
	 */
	protected $response;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Billing' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		wpum_update_option( 'stripe_connect_account_id', 'acct_signature_test' );
		wpum_update_option( 'test_stripe_secret_key', self::SECRET );
		delete_option( 'wpum_stripe_connect_checkout_errors' );

		$this->requests = array();
		$this->response = $this->connect_response( 200, array( 'id' => 'cs_test_signed' ) );

		add_filter( 'pre_http_request', array( $this, 'fake_connect_service' ), 10, 3 );
	}

	public function _tearDown() {
		remove_filter( 'pre_http_request', array( $this, 'fake_connect_service' ), 10 );

		wpum_delete_option( 'stripe_connect_account_id' );
		wpum_delete_option( 'test_stripe_secret_key' );
		delete_option( 'wpum_stripe_connect_checkout_errors' );

		parent::_tearDown();
	}

	public function fake_connect_service( $preempt, $args, $url ) {
		if ( 0 !== strpos( $url, 'https://connect.example.test/checkout' ) ) {
			return $preempt;
		}

		$this->requests[] = $url;

		return $this->response;
	}

	protected function connect_response( $code, $body, $headers = array() ) {
		return array(
			'headers'  => $headers,
			'body'     => wp_json_encode( $body ),
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	protected function billing() {
		return new \WPUserManager\Stripe\Billing( new Connect_Signature_Stripe_Products(), 'https://connect.example.test' );
	}

	protected function customer( $email = null ) {
		$user_id = $this->factory()->user->create( array(
			'user_email' => $email ? $email : 'customer' . wp_rand() . '@example.com',
		) );

		return new \WPUserManager\Stripe\Models\User( $user_id );
	}

	/**
	 * The parameters of the last request made to the Connect service, as it reads them.
	 */
	protected function last_request_params() {
		$this->assertNotEmpty( $this->requests, 'No request was made to the Connect service.' );

		$query = wp_parse_url( end( $this->requests ), PHP_URL_QUERY );
		parse_str( $query, $params );

		return $params;
	}

	protected function sample_params() {
		return array(
			'test_mode'         => 1,
			'plan'              => 'price_premium',
			'success_url'       => 'https://example.com/account/?payment=success&a=b',
			'cancel_url'        => 'https://example.com/account/billing/',
			'user_id'           => 12,
			'stripe_account_id' => 'acct_123',
			'customer_email'    => 'first+last@example.com',
			'amount'            => 10000,
		);
	}

	public function test_canonical_string_is_sorted_and_encoded() {
		$canonical = \WPUserManager\Stripe\Billing::canonicalConnectRequest( array(
			'plan'      => 'price_1',
			'amount'    => 100,
			'email'     => 'a+b@example.com',
			'url'       => 'https://example.com/?x=1&y=2',
			'empty'     => '',
			'skipped'   => null,
			'signature' => 'ignored',
		) );

		$this->assertSame(
			'amount=100&email=a%2Bb%40example.com&empty=&plan=price_1&url=https%3A%2F%2Fexample.com%2F%3Fx%3D1%26y%3D2',
			$canonical
		);
	}

	public function test_canonical_string_does_not_depend_on_parameter_order() {
		$params   = $this->sample_params();
		$reversed = array_reverse( $params, true );

		$this->assertSame(
			\WPUserManager\Stripe\Billing::canonicalConnectRequest( $params ),
			\WPUserManager\Stripe\Billing::canonicalConnectRequest( $reversed )
		);

		$this->assertSame(
			\WPUserManager\Stripe\Billing::signConnectRequest( $params, self::SECRET, 1700000000 ),
			\WPUserManager\Stripe\Billing::signConnectRequest( $reversed, self::SECRET, 1700000000 )
		);
	}

	public function test_signature_is_hmac_sha256_of_the_canonical_string() {
		$signed = \WPUserManager\Stripe\Billing::signConnectRequest( $this->sample_params(), self::SECRET, 1700000000 );

		$this->assertSame( '1700000000', $signed['timestamp'] );

		$unsigned = $signed;
		unset( $unsigned['signature'] );
		ksort( $unsigned );
		$expected = hash_hmac( 'sha256', http_build_query( $unsigned, '', '&', PHP_QUERY_RFC3986 ), self::SECRET );

		$this->assertSame( $expected, $signed['signature'] );
	}

	public function test_signature_changes_when_any_parameter_changes() {
		$params   = $this->sample_params();
		$original = \WPUserManager\Stripe\Billing::signConnectRequest( $params, self::SECRET, 1700000000 );

		foreach ( array_keys( $params ) as $key ) {
			$changed         = $params;
			$changed[ $key ] = $params[ $key ] . 'x';

			$signed = \WPUserManager\Stripe\Billing::signConnectRequest( $changed, self::SECRET, 1700000000 );
			$this->assertNotSame( $original['signature'], $signed['signature'], "Changing {$key} must change the signature." );
		}

		$added          = $params;
		$added['extra'] = '1';
		$signed         = \WPUserManager\Stripe\Billing::signConnectRequest( $added, self::SECRET, 1700000000 );
		$this->assertNotSame( $original['signature'], $signed['signature'], 'Adding a parameter must change the signature.' );

		$later = \WPUserManager\Stripe\Billing::signConnectRequest( $params, self::SECRET, 1700000001 );
		$this->assertNotSame( $original['signature'], $later['signature'], 'The timestamp must be signed.' );

		$other_key = \WPUserManager\Stripe\Billing::signConnectRequest( $params, self::SECRET . 'x', 1700000000 );
		$this->assertNotSame( $original['signature'], $other_key['signature'], 'The signature must depend on the key.' );
	}

	public function test_request_without_a_secret_is_left_unsigned() {
		$params = \WPUserManager\Stripe\Billing::signConnectRequest( $this->sample_params(), '' );

		$this->assertArrayNotHasKey( 'signature', $params );
		$this->assertArrayNotHasKey( 'timestamp', $params );
	}

	public function test_checkout_request_is_signed_and_never_contains_the_secret() {
		$user = $this->customer( 'first+last' . wp_rand() . '@example.com' );

		$session_id = $this->billing()->createStripeCheckoutSession( true, $user, 'price_premium', 'https://example.com/account/?payment=success&a=b' );

		$this->assertSame( 'cs_test_signed', $session_id );
		$this->assertCount( 1, $this->requests );
		$this->assertStringNotContainsString( self::SECRET, $this->requests[0] );
		$this->assertStringNotContainsString( rawurlencode( self::SECRET ), $this->requests[0] );

		$params = $this->last_request_params();

		// The Connect service reads the real values, not double-encoded ones.
		$this->assertSame( $user->email, $params['customer_email'] );
		$this->assertSame( 'https://example.com/account/?payment=success&a=b', $params['success_url'] );
		$this->assertSame( 'acct_signature_test', $params['stripe_account_id'] );
		$this->assertSame( '1', $params['test_mode'] );
		$this->assertSame( '10000', $params['amount'] );
		$this->assertEqualsWithDelta( time(), (int) $params['timestamp'], 60 );

		// What the Connect service will check, from what it received.
		$expected = hash_hmac( 'sha256', \WPUserManager\Stripe\Billing::canonicalConnectRequest( $params ), self::SECRET );
		$this->assertSame( $expected, $params['signature'] );
	}

	public function test_subscription_checkout_request_is_signed() {
		$this->billing()->createStripeCheckoutSession( true, $this->customer(), 'price_monthly' );

		$params = $this->last_request_params();

		$this->assertArrayNotHasKey( 'amount', $params );
		$this->assertSame( 'price_monthly', $params['plan'] );
		$this->assertSame( hash_hmac( 'sha256', \WPUserManager\Stripe\Billing::canonicalConnectRequest( $params ), self::SECRET ), $params['signature'] );
	}

	public function test_live_mode_signs_with_the_live_key() {
		wpum_update_option( 'live_stripe_secret_key', 'sk_live_connect_signature_secret' );

		$this->billing()->createStripeCheckoutSession( false, $this->customer(), 'price_premium' );

		wpum_delete_option( 'live_stripe_secret_key' );

		$params = $this->last_request_params();

		$this->assertSame( '0', $params['test_mode'] );
		$this->assertSame( hash_hmac( 'sha256', \WPUserManager\Stripe\Billing::canonicalConnectRequest( $params ), 'sk_live_connect_signature_secret' ), $params['signature'] );
	}

	public function test_rejected_request_returns_false_and_records_the_error() {
		$errors         = array();
		$this->response = $this->connect_response( 403, array(
			'error'   => 'invalid_signature',
			'message' => 'The request signature does not match this Stripe connection.',
		) );

		add_action( 'wpum_stripe_connect_checkout_error', function ( $code, $mode ) use ( &$errors ) {
			$errors[] = array( $code, $mode );
		}, 10, 2 );

		$connect = new \WPUserManager\Stripe\Connect();

		$this->assertFalse( $this->billing()->createStripeCheckoutSession( true, $this->customer(), 'price_premium' ) );
		$this->assertSame( array( array( 'invalid_signature', 'test' ) ), $errors );
		$this->assertStringContainsString( 'does not match your Stripe connection', $connect->get_checkout_error_message( 'test' ) );
		$this->assertSame( '', $connect->get_checkout_error_message( 'live' ) );

		// A verified request clears it.
		$this->response = $this->connect_response( 200, array( 'id' => 'cs_test_signed' ) );
		$this->assertSame( 'cs_test_signed', $this->billing()->createStripeCheckoutSession( true, $this->customer(), 'price_premium' ) );
		$this->assertSame( '', $connect->get_checkout_error_message( 'test' ) );
		$this->assertFalse( get_option( 'wpum_stripe_connect_checkout_errors' ) );
	}

	public function test_accepted_but_unverified_request_records_the_warning() {
		$this->response = $this->connect_response( 200, array( 'id' => 'cs_test_unverified' ), array( 'x-wpum-connect-signature' => 'signature_expired' ) );

		$this->assertSame( 'cs_test_unverified', $this->billing()->createStripeCheckoutSession( true, $this->customer(), 'price_premium' ) );
		$this->assertStringContainsString( 'server clock', ( new \WPUserManager\Stripe\Connect() )->get_checkout_error_message( 'test' ) );
	}

	public function test_unknown_error_codes_are_not_recorded() {
		$this->response = $this->connect_response( 403, array( 'error' => '<script>' ) );

		$this->assertFalse( $this->billing()->createStripeCheckoutSession( true, $this->customer(), 'price_premium' ) );
		$this->assertFalse( get_option( 'wpum_stripe_connect_checkout_errors' ) );
	}

	public function test_no_request_without_a_connected_account() {
		wpum_delete_option( 'stripe_connect_account_id' );

		$this->assertFalse( $this->billing()->createStripeCheckoutSession( true, $this->customer(), 'price_premium' ) );
		$this->assertEmpty( $this->requests );
	}
}
