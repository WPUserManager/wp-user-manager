<?php
/**
 * A Stripe API error, such as a rate limit or an outage, must not take down the
 * request that happens to load the product list. The last list fetched
 * successfully is used instead, and a failure is only cached for a few minutes.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ProductsApiErrorTest extends WPUMTestCase {

	/**
	 * Stripe HTTP client that answers from a fixed catalogue instead of calling the API.
	 */
	protected $http;

	/**
	 * Expiry of each write to the products transient, in seconds.
	 *
	 * @var int[]
	 */
	protected $cached_for = array();

	/**
	 * @var string|null
	 */
	protected $api_key;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Controllers\Products' ) || ! interface_exists( 'WPUM\Stripe\HttpClient\ClientInterface' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$this->http = new class() implements \WPUM\Stripe\HttpClient\ClientInterface {
			/**
			 * API paths that answer with HTTP 429.
			 *
			 * @var string[]
			 */
			public $rate_limited = array();

			/**
			 * @var string[]
			 */
			public $requests = array();

			public function request( $method, $absUrl, $headers, $params, $hasFile ) {
				$path             = wp_parse_url( $absUrl, PHP_URL_PATH );
				$this->requests[] = $path;

				if ( in_array( $path, $this->rate_limited, true ) ) {
					$error = array(
						'error' => array(
							'message' => 'Request rate limit exceeded.',
							'type'    => 'invalid_request_error',
						),
					);

					return array( wp_json_encode( $error ), 429, array() );
				}

				$data = array();
				if ( '/v1/products' === $path ) {
					$data[] = array( 'id' => 'prod_premium', 'object' => 'product', 'name' => 'Premium', 'type' => 'service' );
				} elseif ( '/v1/prices' === $path ) {
					$data[] = array( 'id' => 'price_premium', 'object' => 'price', 'product' => 'prod_premium', 'type' => 'one_time', 'unit_amount' => 10000, 'currency' => 'usd' );
				}

				return array( wp_json_encode( array( 'object' => 'list', 'data' => $data, 'has_more' => false, 'url' => $path ) ), 200, array() );
			}
		};

		$this->api_key = \WPUM\Stripe\Stripe::getApiKey();
		\WPUM\Stripe\ApiRequestor::setHttpClient( $this->http );
		\WPUserManager\Stripe\Controllers\Products::forget( 'test' );

		$this->cached_for = array();
		add_action( 'set_transient_wpum_test_stripe_products', array( $this, 'record_expiry' ), 10, 2 );
	}

	public function _tearDown() {
		remove_action( 'set_transient_wpum_test_stripe_products', array( $this, 'record_expiry' ), 10 );

		if ( class_exists( 'WPUM\Stripe\ApiRequestor' ) ) {
			\WPUM\Stripe\ApiRequestor::setHttpClient( null );
			\WPUM\Stripe\Stripe::setApiKey( $this->api_key );
			\WPUserManager\Stripe\Controllers\Products::forget( 'test' );
		}

		parent::_tearDown();
	}

	public function record_expiry( $value, $expiration ) {
		$this->cached_for[] = $expiration;
	}

	/**
	 * Build the products controller, as every request does when Stripe is connected.
	 */
	protected function products() {
		return new \WPUserManager\Stripe\Controllers\Products( 'sk_test_fake', 'test' );
	}

	public function test_products_are_cached_for_a_day() {
		$products = $this->products();

		$this->assertNotFalse( $products->get_by_plan( 'price_premium' ) );
		$this->assertSame( array( DAY_IN_SECONDS ), $this->cached_for );
		$this->assertSame( array( '/v1/products', '/v1/prices' ), $this->http->requests );
	}

	public function test_rate_limit_on_products_keeps_the_last_good_list() {
		$this->products();
		delete_transient( 'wpum_test_stripe_products' );
		$this->http->rate_limited = array( '/v1/products' );

		$products = $this->products();

		$this->assertNotFalse( $products->get_by_plan( 'price_premium' ), 'The plan should still resolve from the last good list' );
		$this->assertCount( 1, $products->get_plans() );
	}

	public function test_rate_limit_on_prices_keeps_the_last_good_list() {
		$this->products();
		delete_transient( 'wpum_test_stripe_products' );
		$this->http->rate_limited = array( '/v1/prices' );

		$products = $this->products();

		$this->assertNotFalse( $products->get_by_plan( 'price_premium' ), 'A product list missing its prices must not replace the last good list' );
	}

	public function test_rate_limit_retries_after_a_few_minutes_not_on_every_request() {
		$this->products();
		delete_transient( 'wpum_test_stripe_products' );
		$this->http->rate_limited = array( '/v1/products' );
		$this->http->requests     = array();
		$this->cached_for         = array();

		$this->products();
		$this->products();

		$this->assertSame( array( '/v1/products' ), $this->http->requests, 'Stripe should be asked once, not on every request' );
		$this->assertCount( 1, $this->cached_for );
		$this->assertLessThanOrEqual( 5 * MINUTE_IN_SECONDS, $this->cached_for[0] );
	}

	public function test_rate_limit_with_no_good_list_returns_no_products() {
		$this->http->rate_limited = array( '/v1/products' );

		$products = $this->products();

		$this->assertSame( array(), $products->all() );
		$this->assertFalse( $products->get_by_plan( 'price_premium' ) );
		$this->assertSame( 0, $products->totalRecurringProducts() );
		$this->assertCount( 1, $this->cached_for );
		$this->assertLessThanOrEqual( 5 * MINUTE_IN_SECONDS, $this->cached_for[0], 'A failed fetch must not be cached for a day' );
		$this->assertFalse( get_option( 'wpum_test_stripe_products_last_good' ) );
	}

	public function test_forced_refresh_that_fails_keeps_the_last_good_list() {
		$products                 = $this->products();
		$this->http->rate_limited = array( '/v1/products' );

		$this->assertNotEmpty( $products->all( true ) );
	}

	public function test_forget_drops_the_last_good_list() {
		$this->products();
		\WPUserManager\Stripe\Controllers\Products::forget( 'test' );
		$this->http->rate_limited = array( '/v1/products' );

		$this->assertSame( array(), $this->products()->all(), 'A new Stripe account must not see the old account\'s products' );
	}

	public function test_billing_portal_error_returns_false() {
		$this->http->rate_limited = array( '/v1/billing_portal/sessions' );
		$billing                  = new \WPUserManager\Stripe\Billing( $this->products(), 'https://example.org' );

		$this->assertFalse( $billing->createStripePortalSession( 'sk_test_fake', 'cus_test', 'https://example.org/account/billing' ) );
	}
}
