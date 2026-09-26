<?php
/**
 * Users who still owe a payment keep only read access, on every part of the
 * site, not just the front-end pages covered by the Billing redirect.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class UnpaidCapabilitiesTest extends WPUMTestCase {

	/**
	 * @var \WPUserManager\Stripe\Account
	 */
	protected $account;

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

		add_filter( 'user_has_cap', array( $this->account, 'restrict_unpaid_capabilities' ), 10, 4 );
		add_action( 'added_user_meta', array( $this->account, 'flush_payment_status' ), 10, 2 );
		add_action( 'updated_user_meta', array( $this->account, 'flush_payment_status' ), 10, 2 );
	}

	public function _tearDown() {
		remove_filter( 'user_has_cap', array( $this->account, 'restrict_unpaid_capabilities' ), 10 );
		remove_action( 'added_user_meta', array( $this->account, 'flush_payment_status' ), 10 );
		remove_action( 'updated_user_meta', array( $this->account, 'flush_payment_status' ), 10 );

		parent::_tearDown();
	}

	protected function user_with_plan( $role, $type, $paid ) {
		$user_id = $this->factory()->user->create( array( 'role' => $role ) );
		$product = new \WPUserManager\Stripe\Models\Product( 'price_test', array( 'name' => 'Plan' ), array( 'type' => $type, 'unit_amount' => 1000 ) );
		if ( $paid ) {
			$product->setPaid();
		}
		( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

		return $user_id;
	}

	public function test_unpaid_one_time_author_can_only_read() {
		$user_id = $this->user_with_plan( 'author', 'one_time', false );

		$this->assertTrue( user_can( $user_id, 'read' ) );
		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );
		$this->assertFalse( user_can( $user_id, 'publish_posts' ) );
		$this->assertFalse( user_can( $user_id, 'upload_files' ) );
	}

	public function test_paid_one_time_author_keeps_role_capabilities() {
		$user_id = $this->user_with_plan( 'author', 'one_time', true );

		$this->assertTrue( user_can( $user_id, 'edit_posts' ) );
		$this->assertTrue( user_can( $user_id, 'publish_posts' ) );
	}

	public function test_recurring_author_without_active_subscription_can_only_read() {
		$user_id = $this->user_with_plan( 'author', 'recurring', false );

		$this->assertTrue( user_can( $user_id, 'read' ) );
		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );
	}

	public function test_paid_form_without_recorded_plan_fails_closed() {
		$user_id = $this->factory()->user->create( array( 'role' => 'author' ) );
		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );

		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );
	}

	public function test_user_without_stripe_plan_is_unaffected() {
		$user_id = $this->factory()->user->create( array( 'role' => 'author' ) );

		$this->assertTrue( user_can( $user_id, 'edit_posts' ) );
		$this->assertTrue( user_can( $user_id, 'publish_posts' ) );
	}

	public function test_administrator_is_never_restricted() {
		$user_id = $this->user_with_plan( 'administrator', 'one_time', false );

		$this->assertTrue( user_can( $user_id, 'manage_options' ) );
		$this->assertTrue( user_can( $user_id, 'edit_posts' ) );
	}

	public function test_capabilities_return_once_the_plan_is_paid() {
		$user_id = $this->user_with_plan( 'author', 'one_time', false );
		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );

		$product = new \WPUserManager\Stripe\Models\Product( 'price_test', array( 'name' => 'Plan' ), array( 'type' => 'one_time', 'unit_amount' => 1000 ) );
		$product->setPaid();
		( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

		$this->assertTrue( user_can( $user_id, 'edit_posts' ), 'Paying must restore the role without waiting for a new request' );
	}

	public function test_restriction_can_be_turned_off() {
		$user_id = $this->user_with_plan( 'author', 'one_time', false );
		add_filter( 'wpum_stripe_restrict_unpaid_capabilities', '__return_false' );

		$this->assertTrue( user_can( $user_id, 'edit_posts' ) );

		remove_filter( 'wpum_stripe_restrict_unpaid_capabilities', '__return_false' );
	}
}
