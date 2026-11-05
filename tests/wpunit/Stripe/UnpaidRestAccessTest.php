<?php
/**
 * Users who still owe a payment can't read the site through the REST API,
 * which the front-end Billing redirect doesn't cover.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class UnpaidRestAccessTest extends WPUMTestCase {

	/**
	 * @var \WPUserManager\Stripe\Account
	 */
	protected $account;

	/**
	 * @var int
	 */
	protected $post_id;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Account' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$cache = new \ReflectionProperty( \WPUserManager\Stripe\Account::class, 'owes_payment' );
		$cache->setAccessible( true );
		$cache->setValue( null, array() );

		$ref           = new \ReflectionClass( \WPUserManager\Stripe\Account::class );
		$this->account = $ref->newInstanceWithoutConstructor();

		add_filter( 'rest_pre_dispatch', array( $this->account, 'refuse_unpaid_rest_requests' ), 10, 3 );
		add_action( 'added_user_meta', array( $this->account, 'flush_payment_status' ), 10, 2 );
		add_action( 'updated_user_meta', array( $this->account, 'flush_payment_status' ), 10, 2 );

		$this->post_id = self::factory()->post->create( array( 'post_content' => 'Paid members text' ) );
	}

	public function _tearDown() {
		remove_filter( 'rest_pre_dispatch', array( $this->account, 'refuse_unpaid_rest_requests' ), 10 );
		remove_action( 'added_user_meta', array( $this->account, 'flush_payment_status' ), 10 );
		remove_action( 'updated_user_meta', array( $this->account, 'flush_payment_status' ), 10 );
		wpum_delete_option( 'lock_complete_site' );
		wp_set_current_user( 0 );

		parent::_tearDown();
	}

	protected function user_with_plan( $role, $paid ) {
		$user_id = $this->factory()->user->create( array( 'role' => $role ) );
		$product = new \WPUserManager\Stripe\Models\Product( 'price_test', array( 'name' => 'Plan' ), array( 'type' => 'one_time', 'unit_amount' => 1000 ) );
		if ( $paid ) {
			$product->setPaid();
		}
		( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

		return $user_id;
	}

	protected function get( $route ) {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', $route ) );
	}

	public function test_unpaid_user_cannot_read_a_post_when_site_is_locked() {
		wpum_update_option( 'lock_complete_site', true );
		wp_set_current_user( $this->user_with_plan( 'subscriber', false ) );

		$response = $this->get( '/wp/v2/posts/' . $this->post_id );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'wpum_payment_required', $response->get_data()['code'] );
	}

	public function test_unpaid_user_cannot_list_posts_or_pages() {
		wpum_update_option( 'lock_complete_site', true );
		wp_set_current_user( $this->user_with_plan( 'subscriber', false ) );

		$this->assertSame( 403, $this->get( '/wp/v2/posts' )->get_status() );
		$this->assertSame( 403, $this->get( '/wp/v2/pages' )->get_status() );
	}

	public function test_unpaid_user_is_refused_when_site_is_not_locked() {
		wp_set_current_user( $this->user_with_plan( 'subscriber', false ) );

		$this->assertSame( 403, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );
	}

	public function test_paid_user_can_read_posts() {
		wpum_update_option( 'lock_complete_site', true );
		wp_set_current_user( $this->user_with_plan( 'subscriber', true ) );

		$this->assertSame( 200, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );
	}

	public function test_user_without_stripe_plan_is_unaffected() {
		wpum_update_option( 'lock_complete_site', true );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 200, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );
	}

	public function test_paid_form_without_recorded_plan_fails_closed() {
		$user_id = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );
		wp_set_current_user( $user_id );

		$this->assertSame( 403, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );
	}

	public function test_logged_out_visitor_is_unaffected_when_site_is_not_locked() {
		wp_set_current_user( 0 );

		$this->assertSame( 200, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );
	}

	public function test_access_returns_once_the_plan_is_paid() {
		$user_id = $this->user_with_plan( 'subscriber', false );
		wp_set_current_user( $user_id );
		$this->assertSame( 403, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );

		$product = new \WPUserManager\Stripe\Models\Product( 'price_test', array( 'name' => 'Plan' ), array( 'type' => 'one_time', 'unit_amount' => 1000 ) );
		$product->setPaid();
		( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

		$this->assertSame( 200, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );
	}

	public function test_stripe_webhook_route_stays_open() {
		wp_set_current_user( $this->user_with_plan( 'subscriber', false ) );

		$request = new WP_REST_Request( 'POST', '/wpum/v1/stripe' );
		$this->assertNull( $this->account->refuse_unpaid_rest_requests( null, rest_get_server(), $request ) );
	}

	public function test_allowed_routes_can_be_filtered() {
		wp_set_current_user( $this->user_with_plan( 'subscriber', false ) );
		$allow = function ( $routes ) {
			$routes[] = '/wp/v2/users/me';
			return $routes;
		};
		add_filter( 'wpum_stripe_not_paid_allowed_rest_routes', $allow );

		$this->assertSame( 200, $this->get( '/wp/v2/users/me' )->get_status() );
		$this->assertSame( 403, $this->get( '/wp/v2/posts/' . $this->post_id )->get_status() );

		remove_filter( 'wpum_stripe_not_paid_allowed_rest_routes', $allow );
	}
}
