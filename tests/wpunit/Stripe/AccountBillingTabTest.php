<?php
/**
 * The Billing account tab must be available to anyone who still has to pay,
 * including one-time buyers who abandoned Checkout.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class AccountBillingTabTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Account' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}
	}

	protected function tabs_for( $user_id ) {
		wp_set_current_user( $user_id );

		$ref     = new \ReflectionClass( \WPUserManager\Stripe\Account::class );
		$account = $ref->newInstanceWithoutConstructor();

		return array_keys( $account->register_account_tab( array( 'settings' => array() ) ) );
	}

	protected function user_with_plan( $type, $paid ) {
		$user_id = $this->factory()->user->create();
		$product = new \WPUserManager\Stripe\Models\Product( 'price_test', array( 'name' => 'Plan' ), array( 'type' => $type, 'unit_amount' => 1000 ) );
		if ( $paid ) {
			$product->setPaid();
		}
		( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

		return $user_id;
	}

	public function test_unpaid_one_time_buyer_can_reach_billing() {
		$this->assertContains( 'billing', $this->tabs_for( $this->user_with_plan( 'one_time', false ) ), 'An abandoned one-time checkout must leave a way to pay' );
	}

	public function test_paid_one_time_buyer_has_no_billing_tab() {
		$this->assertNotContains( 'billing', $this->tabs_for( $this->user_with_plan( 'one_time', true ) ) );
	}

	public function test_recurring_plan_has_billing_tab() {
		$this->assertContains( 'billing', $this->tabs_for( $this->user_with_plan( 'recurring', false ) ) );
	}

	public function test_user_without_stripe_plan_has_no_billing_tab() {
		$this->assertNotContains( 'billing', $this->tabs_for( $this->factory()->user->create() ) );
	}
}
