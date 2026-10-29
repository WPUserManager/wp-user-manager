<?php
/**
 * Gaps in the Stripe product list (#527), ported from the Stripe Pro addon's
 * known-bug tests: prices past the first 10, products past the first 100, the
 * Fetch Stripe Products button with one product, and checkout for a price that
 * is no longer in the list.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/527
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ProductListGapsTest extends WPUMTestCase {

	/**
	 * Stripe HTTP client that answers from a catalogue and pages lists as Stripe does.
	 */
	protected $http;

	/**
	 * @var string|null
	 */
	protected $api_key;

	/**
	 * @var int
	 */
	protected $http_requests = 0;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Controllers\Products' ) || ! interface_exists( 'WPUM\Stripe\HttpClient\ClientInterface' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$this->http = new class() implements \WPUM\Stripe\HttpClient\ClientInterface {
			/**
			 * Products, each with a 'prices' list.
			 *
			 * @var array[]
			 */
			public $products = array();

			/**
			 * Every request: path and params.
			 *
			 * @var array[]
			 */
			public $requests = array();

			public function add_product( $id, $price_ids ) {
				$prices = array();
				foreach ( $price_ids as $i => $price_id ) {
					$prices[] = array(
						'id'          => $price_id,
						'object'      => 'price',
						'product'     => $id,
						'type'        => 'one_time',
						'unit_amount' => 1000 + $i,
						'currency'    => 'usd',
						'active'      => true,
					);
				}

				$this->products[] = array(
					'id'     => $id,
					'object' => 'product',
					'name'   => $id,
					'type'   => 'service',
					'active' => true,
					'prices' => $prices,
				);
			}

			public function request( $method, $absUrl, $headers, $params, $hasFile ) {
				$path = wp_parse_url( $absUrl, PHP_URL_PATH );
				parse_str( (string) wp_parse_url( $absUrl, PHP_URL_QUERY ), $query );
				$params           = array_merge( $query, (array) $params );
				$this->requests[] = array(
					'path'   => $path,
					'params' => $params,
				);

				$items = array();
				if ( '/v1/products' === $path ) {
					foreach ( $this->products as $product ) {
						unset( $product['prices'] );
						$items[] = $product;
					}
				} elseif ( '/v1/prices' === $path ) {
					foreach ( $this->products as $product ) {
						if ( isset( $params['product'] ) && $product['id'] === $params['product'] ) {
							$items = $product['prices'];
						}
					}
				}

				// Page as Stripe does: `limit` items (10 by default) after `starting_after`.
				if ( ! empty( $params['starting_after'] ) ) {
					$ids   = wp_list_pluck( $items, 'id' );
					$index = array_search( $params['starting_after'], $ids, true );
					$items = false === $index ? array() : array_slice( $items, $index + 1 );
				}
				$limit    = isset( $params['limit'] ) ? (int) $params['limit'] : 10;
				$has_more = count( $items ) > $limit;
				$items    = array_slice( $items, 0, $limit );

				$body = array(
					'object'   => 'list',
					'data'     => array_values( $items ),
					'has_more' => $has_more,
					'url'      => $path,
				);

				return array( wp_json_encode( $body ), 200, array() );
			}

			public function requests_to( $path ) {
				return array_values(
					array_filter(
						$this->requests,
						function ( $request ) use ( $path ) {
							return $path === $request['path'];
						}
					)
				);
			}
		};

		$this->api_key = \WPUM\Stripe\Stripe::getApiKey();
		\WPUM\Stripe\ApiRequestor::setHttpClient( $this->http );
		\WPUserManager\Stripe\Controllers\Products::forget( 'test' );

		$this->http->add_product( 'prod_annual', array( 'price_annual' ) );
	}

	public function _tearDown() {
		if ( class_exists( 'WPUM\Stripe\ApiRequestor' ) ) {
			\WPUM\Stripe\ApiRequestor::setHttpClient( null );
			\WPUM\Stripe\Stripe::setApiKey( $this->api_key );
			\WPUserManager\Stripe\Controllers\Products::forget( 'test' );
		}
		remove_all_filters( 'pre_http_request' );
		wpum_delete_option( 'stripe_connect_account_id' );

		parent::_tearDown();
	}

	protected function products() {
		return new \WPUserManager\Stripe\Controllers\Products( 'sk_test_fake', 'test' );
	}

	protected function listed_prices() {
		return wp_list_pluck( $this->products()->get_plans(), 'value' );
	}

	public function test_every_price_of_a_product_with_many_prices_is_listed() {
		// 101 prices: more than one page of 100, so the second page must be fetched.
		$tiers = array();
		for ( $i = 1; $i <= 101; $i++ ) {
			$tiers[] = 'price_tier_' . $i;
		}
		$this->http->add_product( 'prod_tiers', $tiers );

		$prices = $this->listed_prices();

		$this->assertCount( 102, $prices );
		$this->assertContains( 'price_tier_101', $prices );

		$tier_requests = array_filter(
			$this->http->requests_to( '/v1/prices' ),
			function ( $request ) {
				return isset( $request['params']['product'] ) && 'prod_tiers' === $request['params']['product'];
			}
		);
		$this->assertCount( 2, $tier_requests, 'The second page of prices should be fetched.' );
	}

	public function test_more_than_a_hundred_products_are_all_listed() {
		for ( $i = 1; $i <= 100; $i++ ) {
			$this->http->add_product( 'prod_many_' . $i, array( 'price_many_' . $i ) );
		}

		$prices = $this->listed_prices();

		$this->assertCount( 101, $prices );
		$this->assertContains( 'price_annual', $prices );
		$this->assertContains( 'price_many_100', $prices );
		$this->assertCount( 2, $this->http->requests_to( '/v1/products' ), 'The second page of products should be fetched.' );
	}

	public function test_products_and_prices_are_requested_a_hundred_at_a_time() {
		$this->products();

		$this->assertEquals( 100, $this->http->requests_to( '/v1/products' )[0]['params']['limit'] );
		$this->assertEquals( 100, $this->http->requests_to( '/v1/prices' )[0]['params']['limit'] );
	}

	public function test_fetch_products_button_shows_with_one_product_cached() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$settings = new \WPUserManager\Stripe\Settings( new \WPUserManager\Stripe\Connect() );
		$settings->setProducts( $this->products() );

		$this->assertStringContainsString( 'fetch-products', wp_json_encode( $settings->register_settings( array() ) ) );
	}

	public function test_checkout_for_a_price_missing_from_the_product_list_returns_false() {
		wpum_update_option( 'stripe_connect_account_id', 'acct_test' );
		add_filter(
			'pre_http_request',
			function ( $pre ) {
				++$this->http_requests;

				return $pre;
			}
		);

		$billing = new \WPUserManager\Stripe\Billing( $this->products(), 'https://connect.example.org' );
		$user    = new \WPUserManager\Stripe\Models\User( self::factory()->user->create() );

		$this->assertFalse( $billing->createStripeCheckoutSession( true, $user, 'price_archived', 'https://example.org/' ) );
		$this->assertSame( 0, $this->http_requests, 'No checkout request should be sent.' );
	}

	public function test_redirect_after_payment_for_a_missing_price_does_not_fatal() {
		$products = $this->products();
		$billing  = new \WPUserManager\Stripe\Billing( $products, 'https://connect.example.org' );
		$account  = new \WPUserManager\Stripe\Account( 'pk_test_fake', 'sk_test_fake', 'test', $billing, $products );

		$this->assertIsString( $account->get_redirect_after_account_payment( 'price_archived' ) );
	}
}
