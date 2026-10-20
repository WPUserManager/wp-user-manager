<?php
/**
 * A customer can only pay for the plans they signed up for, and a payment
 * only counts towards the plan that was paid for. Paying for a cheaper plan
 * must not unlock the plan the registration form required.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

if ( class_exists( 'WPUserManager\Stripe\Controllers\Products' ) && ! class_exists( 'Plan_Binding_Stripe_Products' ) ) {
	/**
	 * Products controller backed by a fixed catalogue instead of the Stripe API.
	 */
	class Plan_Binding_Stripe_Products extends \WPUserManager\Stripe\Controllers\Products {

		public function __construct() {
			$this->gateway_mode = 'test';
			$this->products     = $this->all();
		}

		public function all( $force = false ) {
			return array(
				'prod_premium' => array(
					'name'   => 'Premium',
					'prices' => array(
						'price_premium'     => array( 'type' => 'one_time', 'unit_amount' => 10000, 'currency' => 'usd' ),
						'price_premium_alt' => array( 'type' => 'one_time', 'unit_amount' => 9000, 'currency' => 'usd' ),
					),
				),
				'prod_cheap'   => array(
					'name'   => 'Cheap',
					'prices' => array(
						'price_cheap' => array( 'type' => 'one_time', 'unit_amount' => 100, 'currency' => 'usd' ),
					),
				),
			);
		}
	}
}

class PlanBindingTest extends WPUMTestCase {

	/**
	 * @var \WPUM_Registration_Form
	 */
	protected $registration_form;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$forms                   = WPUM()->registration_forms->get_forms();
		$this->registration_form = $forms[0];
		$this->registration_form->update_meta( 'stripe_plan_id', array( 'price_premium', 'price_premium_alt' ) );
	}

	public function _tearDown() {
		delete_transient( 'wpum_test_stripe_products' );
		delete_option( 'wpum_test_stripe_products_last_good' );
		wpum_delete_option( 'test_stripe_products' );

		if ( $this->registration_form ) {
			$this->registration_form->delete_meta( 'stripe_plan_id', '' );
		}

		parent::_tearDown();
	}

	/**
	 * A customer who registered for a plan and hasn't paid, as left by an abandoned checkout.
	 */
	protected function unpaid_customer( $plan_id = 'price_premium', $type = 'one_time' ) {
		$user_id = $this->factory()->user->create( array(
			'role'       => 'contributor',
			'user_email' => 'customer' . wp_rand() . '@example.com',
		) );

		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );
		update_user_meta( $user_id, 'wpum_form_id', $this->registration_form->get_ID() );

		$product = new \WPUserManager\Stripe\Models\Product( $plan_id, array( 'name' => 'Premium' ), array( 'type' => $type, 'unit_amount' => 10000 ) );
		( new \WPUserManager\Stripe\Models\User( $user_id ) )->setPlanMeta( $product->to_array() );

		return get_userdata( $user_id );
	}

	protected function plan_meta( $user_id ) {
		return ( new \WPUserManager\Stripe\Models\User( $user_id ) )->getPlanMeta();
	}

	/**
	 * Webhook controller whose Checkout Session lookup returns the given price instead of calling Stripe.
	 */
	protected function webhook_paying_for( $price_id, $type = 'one_time' ) {
		$controller = new class( '', 'whsec_test', 'test' ) extends \WPUserManager\Stripe\StripeWebhookController {
			public $paid_line_item;

			protected function retrieveSessionLineItem( $session_id ) {
				return $this->paid_line_item;
			}

			public function handle( $method, $payload ) {
				return $this->{$method}( $payload );
			}
		};

		$controller->paid_line_item = array(
			'description' => 'Paid plan',
			'price'       => array( 'id' => $price_id, 'type' => $type, 'unit_amount' => 100 ),
		);

		return $controller;
	}

	protected function session_payload( $email, $subscription = null ) {
		return array(
			'data' => array(
				'object' => array(
					'id'             => 'cs_test',
					'customer_email' => $email,
					'customer'       => 'cus_test_' . wp_rand(),
					'subscription'   => $subscription,
					'payment_status' => 'paid',
				),
			),
		);
	}

	/**
	 * A customer subscribed to the plan they registered for.
	 */
	protected function subscribed_customer( $plan_id = 'price_premium' ) {
		$this->require_subscriptions_table();
		$customer = $this->unpaid_customer( $plan_id, 'recurring' );
		$sub_id   = 'sub_' . wp_rand();

		( new \WPUserManager\Stripe\Controllers\Subscriptions( 'test' ) )->insert( array(
			'user_id'         => $customer->ID,
			'customer_id'     => 'cus_' . wp_rand(),
			'subscription_id' => $sub_id,
			'plan_id'         => $plan_id,
			'trial_ends_at'   => null,
			'ends_at'         => null,
		) );

		return array( $customer, $sub_id );
	}

	/**
	 * A customer.subscription.updated event, as sent when the customer switches price in the Stripe customer portal.
	 */
	protected function subscription_updated_payload( $sub_id, $price_id, $top_level_plan = true ) {
		$object = array(
			'id'                   => $sub_id,
			'cancel_at_period_end' => false,
			'current_period_end'   => time() + MONTH_IN_SECONDS,
			'items'                => array( 'data' => array( array( 'price' => array( 'id' => $price_id ), 'plan' => array( 'id' => $price_id ) ) ) ),
		);

		if ( $top_level_plan ) {
			$object['plan'] = array( 'id' => $price_id );
		}

		return array( 'data' => array( 'object' => $object ) );
	}

	protected function require_subscriptions_table() {
		global $wpdb;

		$table = $wpdb->prefix . 'wpum_stripe_subscriptions';
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			$this->markTestSkipped( 'Stripe subscriptions table is not installed.' );
		}
	}

	/*
	 * Which plans a customer may pay for.
	 */

	public function test_allowed_plans_are_the_stored_plan_and_the_form_plans() {
		$user = new \WPUserManager\Stripe\Models\User( $this->unpaid_customer()->ID );

		$allowed = $user->getAllowedPlanIds();

		$this->assertContains( 'price_premium', $allowed );
		$this->assertContains( 'price_premium_alt', $allowed );
		$this->assertNotContains( 'price_cheap', $allowed, 'A plan the registration form never offered must not be payable' );
	}

	public function test_billing_tab_lists_only_the_customers_plans() {
		wp_set_current_user( $this->unpaid_customer()->ID );

		$account = new \WPUserManager\Stripe\Account( 'pk_test', 'sk_test', 'test', $this->createMock( \WPUserManager\Stripe\Billing::class ), new Plan_Binding_Stripe_Products() );

		ob_start();
		$account->account_tab_content();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-plan-id="price_premium"', $output );
		$this->assertStringNotContainsString( 'price_cheap', $output, 'The Billing tab must not offer, or issue a checkout nonce for, other plans on the site' );
	}

	public function test_products_are_filtered_to_the_given_prices() {
		$account = new \WPUserManager\Stripe\Account( 'pk_test', 'sk_test', 'test', $this->createMock( \WPUserManager\Stripe\Billing::class ), new Plan_Binding_Stripe_Products() );

		$products = $account->get_products_for_prices( array( 'price_premium' ) );

		$this->assertSame( array( 'prod_premium' ), array_keys( $products ) );
		$this->assertSame( array( 'price_premium' ), array_keys( $products['prod_premium']['prices'] ) );
		$this->assertSame( array(), $account->get_products_for_prices( array() ) );
	}

	/*
	 * One-time payments.
	 */

	public function test_paying_for_a_cheaper_plan_does_not_mark_the_registered_plan_paid() {
		$customer = $this->unpaid_customer();

		$response = $this->webhook_paying_for( 'price_cheap' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( $customer->user_email ) );

		$meta = $this->plan_meta( $customer->ID );
		$this->assertSame( 'price_premium', $meta['id'] );
		$this->assertEmpty( $meta['paid'], 'Paying for price_cheap must not satisfy price_premium' );
		$this->assertSame( 'Plan not allowed for user', $response->get_data() );
		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isPaid() );
	}

	public function test_paying_for_the_registered_plan_marks_it_paid() {
		$customer = $this->unpaid_customer();

		$this->webhook_paying_for( 'price_premium' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( $customer->user_email ) );

		$meta = $this->plan_meta( $customer->ID );
		$this->assertSame( 'price_premium', $meta['id'] );
		$this->assertTrue( $meta['paid'] );
	}

	public function test_paying_for_another_plan_on_the_form_switches_to_it() {
		$customer = $this->unpaid_customer();

		$this->webhook_paying_for( 'price_premium_alt' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( $customer->user_email ) );

		$meta = $this->plan_meta( $customer->ID );
		$this->assertSame( 'price_premium_alt', $meta['id'], 'The stored plan should be the one that was paid for' );
		$this->assertTrue( $meta['paid'] );
	}

	public function test_delayed_payment_for_a_cheaper_plan_does_not_mark_the_registered_plan_paid() {
		$customer = $this->unpaid_customer();

		$this->webhook_paying_for( 'price_cheap' )->handle( 'handleCheckoutSessionAsyncPaymentSucceeded', $this->session_payload( $customer->user_email ) );

		$this->assertEmpty( $this->plan_meta( $customer->ID )['paid'] );
	}

	public function test_payment_for_an_unknown_email_is_ignored() {
		$response = $this->webhook_paying_for( 'price_premium' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( 'nobody' . wp_rand() . '@example.com' ) );

		$this->assertSame( 'User not found', $response->get_data() );
	}

	public function test_user_with_no_plan_to_pay_for_can_still_buy_one() {
		$user_id = $this->factory()->user->create( array( 'user_email' => 'plain' . wp_rand() . '@example.com' ) );

		$this->webhook_paying_for( 'price_cheap' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( get_userdata( $user_id )->user_email ) );

		$meta = $this->plan_meta( $user_id );
		$this->assertSame( 'price_cheap', $meta['id'] );
		$this->assertTrue( $meta['paid'] );
	}

	public function test_paid_form_without_any_plan_rejects_payments() {
		$user_id = $this->factory()->user->create( array( 'user_email' => 'noplan' . wp_rand() . '@example.com' ) );
		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );

		$this->webhook_paying_for( 'price_cheap' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( get_userdata( $user_id )->user_email ) );

		$this->assertEmpty( $this->plan_meta( $user_id ) );
	}

	/*
	 * Subscriptions.
	 */

	public function test_subscribing_to_a_cheaper_plan_does_not_count() {
		$this->require_subscriptions_table();
		$customer = $this->unpaid_customer( 'price_premium', 'recurring' );
		$sub_id   = 'sub_cheap_' . wp_rand();

		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( $customer->user_email, $sub_id ) );

		$user = new \WPUserManager\Stripe\Models\User( $customer->ID );
		$this->assertEmpty( $user->subscription, 'A subscription to another plan must not be recorded' );
		$this->assertFalse( $user->isSubscribed() );
	}

	public function test_subscription_created_event_for_a_cheaper_plan_does_not_count() {
		$this->require_subscriptions_table();
		$customer = $this->unpaid_customer( 'price_premium', 'recurring' );

		$payload = array(
			'data' => array(
				'object' => array(
					'id'       => 'sub_cheap_' . wp_rand(),
					'customer' => 'cus_test_' . wp_rand(),
					'metadata' => array( 'user_id' => $customer->ID ),
					'items'    => array( 'data' => array( array( 'plan' => array( 'id' => 'price_cheap' ) ) ) ),
				),
			),
		);

		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCustomerSubscriptionCreated', $payload );

		$this->assertEmpty( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->subscription );
	}

	public function test_subscribing_to_the_registered_plan_counts() {
		$this->require_subscriptions_table();
		$customer = $this->unpaid_customer( 'price_premium', 'recurring' );

		$this->webhook_paying_for( 'price_premium', 'recurring' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( $customer->user_email, 'sub_premium_' . wp_rand() ) );

		$this->assertTrue( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed() );
	}

	/*
	 * Switching plans in the Stripe customer portal.
	 */

	public function test_switching_to_another_forms_plan_in_the_portal_does_not_count() {
		list( $customer, $sub_id ) = $this->subscribed_customer();
		$this->assertTrue( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed() );

		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_cheap' ) );

		$user = new \WPUserManager\Stripe\Models\User( $customer->ID );
		$this->assertSame( 'price_cheap', $user->subscription->plan_id, 'The stored plan should match Stripe' );
		$this->assertFalse( $user->isSubscribed(), 'A subscription switched to a plan the customer may not pay for must not count' );
		$this->assertFalse( $user->isPlanAllowed( 'price_cheap' ), 'The switched plan must not authorise itself' );
		$this->assertNotContains( 'price_cheap', $user->getAllowedPlanIds() );
		$this->assertFalse( $user->hasPaidByPlan( 'price_cheap' ) );
		$this->assertFalse( $user->hasPaidByPlan( 'price_premium' ) );
	}

	public function test_customer_on_another_forms_plan_owes_payment() {
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_cheap' ) );

		$account = new \WPUserManager\Stripe\Account( 'pk_test', 'sk_test', 'test', $this->createMock( \WPUserManager\Stripe\Billing::class ), new Plan_Binding_Stripe_Products() );
		$this->assertTrue( $account->owes_payment( $customer->ID ) );
	}

	public function test_switching_back_to_the_registered_plan_restores_access() {
		list( $customer, $sub_id ) = $this->subscribed_customer();
		$controller                = $this->webhook_paying_for( 'price_cheap', 'recurring' );

		$controller->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_cheap' ) );
		$controller->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_premium' ) );

		$user = new \WPUserManager\Stripe\Models\User( $customer->ID );
		$this->assertSame( 'price_premium', $user->subscription->plan_id );
		$this->assertTrue( $user->isSubscribed() );
	}

	public function test_switching_to_another_plan_on_the_form_counts() {
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->webhook_paying_for( 'price_premium_alt', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_premium_alt' ) );

		$user = new \WPUserManager\Stripe\Models\User( $customer->ID );
		$this->assertSame( 'price_premium_alt', $user->subscription->plan_id );
		$this->assertTrue( $user->isSubscribed() );
		$this->assertTrue( $user->hasPaidByPlan( 'price_premium_alt' ) );
	}

	public function test_subscription_update_reads_the_price_from_its_items() {
		list( $customer, $sub_id ) = $this->subscribed_customer();

		// Subscriptions with more than one item have no top level plan.
		$this->webhook_paying_for( 'price_premium_alt', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_premium_alt', false ) );

		$this->assertSame( 'price_premium_alt', ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->subscription->plan_id );
	}

	public function test_billing_tab_does_not_offer_another_forms_plan_after_a_switch() {
		list( $customer, $sub_id ) = $this->subscribed_customer();
		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_cheap' ) );

		$subscriptions = new \WPUserManager\Stripe\Controllers\Subscriptions( 'test' );
		$row           = $subscriptions->where( 'subscription_id', $sub_id );
		$subscriptions->update( $row->id, array( 'ends_at' => '2020-01-01 00:00:00' ) );

		wp_set_current_user( $customer->ID );
		$account = new \WPUserManager\Stripe\Account( 'pk_test', 'sk_test', 'test', $this->createMock( \WPUserManager\Stripe\Billing::class ), new Plan_Binding_Stripe_Products() );

		ob_start();
		$account->account_tab_content();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'data-plan-id="price_premium"', $output );
		$this->assertStringNotContainsString( 'data-plan-id="price_cheap"', $output );
	}

	public function test_billing_tab_explains_a_plan_that_isnt_allowed() {
		list( $customer, $sub_id ) = $this->subscribed_customer();
		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_cheap' ) );

		wp_set_current_user( $customer->ID );
		$account = new \WPUserManager\Stripe\Account( 'pk_test', 'sk_test', 'test', $this->createMock( \WPUserManager\Stripe\Billing::class ), new Plan_Binding_Stripe_Products() );

		ob_start();
		$account->account_tab_content();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'switch back to your plan', $output );
		$this->assertStringContainsString( 'wpum-stripe-manage-billing', $output, 'They need Manage Billing to switch back' );
	}

	/*
	 * Upgrading in the Stripe customer portal.
	 */

	/**
	 * The cached Stripe product list, with recurring prices.
	 */
	protected function cache_recurring_prices() {
		$monthly = function ( $amount, $currency = 'usd', $interval = 'month' ) {
			return array(
				'unit_amount' => $amount,
				'currency'    => $currency,
				'type'        => 'recurring',
				'recurring'   => array( 'interval' => $interval, 'interval_count' => 1 ),
			);
		};

		set_transient( 'wpum_test_stripe_products', array(
			array(
				'name'   => 'Premium',
				'prices' => array(
					'price_premium'     => $monthly( 10000 ),
					'price_premium_alt' => $monthly( 9000 ),
				),
			),
			array(
				'name'   => 'Cheap',
				'prices' => array( 'price_cheap' => $monthly( 100 ) ),
			),
			array(
				'name'   => 'Gold',
				'prices' => array(
					'price_gold'        => $monthly( 20000 ),
					'price_gold_annual' => $monthly( 100000, 'usd', 'year' ),
					'price_gold_eur'    => $monthly( 20000, 'eur' ),
				),
			),
		), HOUR_IN_SECONDS );
	}

	protected function switch_to( $sub_id, $price_id ) {
		$this->webhook_paying_for( $price_id, 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, $price_id ) );
	}

	public function test_upgrading_to_a_dearer_plan_from_another_form_keeps_access() {
		$this->cache_recurring_prices();
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_gold' );

		$user = new \WPUserManager\Stripe\Models\User( $customer->ID );
		$this->assertTrue( $user->isSubscribed(), 'Paying more than the registered plan is an upgrade' );
		$this->assertTrue( $user->hasPaidByPlan( 'price_gold' ), 'Content for the plan they upgraded to should open' );
		$this->assertFalse( $user->hasSubscriptionOnDisallowedPlan() );
		$this->assertNotContains( 'price_gold', $user->getAllowedPlanIds(), 'An upgrade is not offered for checkout on the Billing tab' );
	}

	public function test_downgrading_to_a_cheaper_eligible_plan_loses_access() {
		$this->cache_recurring_prices();
		wpum_update_option( 'test_stripe_products', array( 'price_premium', 'price_cheap', 'price_gold' ) );
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_cheap' );

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed(), 'Being an Eligible Product does not make a cheaper plan count' );
	}

	public function test_upgrading_to_a_plan_that_is_not_eligible_loses_access() {
		$this->cache_recurring_prices();
		wpum_update_option( 'test_stripe_products', array( 'price_premium', 'price_cheap' ) );
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_gold' );

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed(), 'Only Eligible Products count as upgrades when they are set' );
	}

	public function test_upgrading_to_an_eligible_plan_keeps_access() {
		$this->cache_recurring_prices();
		wpum_update_option( 'test_stripe_products', array( 'price_premium', 'price_gold' ) );
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_gold' );

		$this->assertTrue( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed() );
	}

	public function test_annual_plan_that_costs_less_per_day_is_not_an_upgrade() {
		$this->cache_recurring_prices();
		list( $customer, $sub_id ) = $this->subscribed_customer();

		// 1000.00 a year is less per day than 100.00 a month.
		$this->switch_to( $sub_id, 'price_gold_annual' );

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed() );
	}

	public function test_plan_in_another_currency_is_not_an_upgrade() {
		$this->cache_recurring_prices();
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_gold_eur' );

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed() );
	}

	public function test_plan_missing_from_the_product_cache_is_not_an_upgrade() {
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_gold' );

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->isSubscribed(), 'Without price data an unknown plan fails closed' );
	}

	public function test_upgrade_does_not_put_the_member_on_the_admin_list() {
		$this->cache_recurring_prices();
		list( $customer, $sub_id ) = $this->subscribed_customer();

		$this->switch_to( $sub_id, 'price_gold' );

		$this->assertArrayNotHasKey( $customer->ID, ( new \WPUserManager\Stripe\SubscriptionPlanCheck( 'test' ) )->get_affected_users() );
	}

	/*
	 * Telling site admins which members are on a plan they may not pay for.
	 */

	public function test_scan_finds_members_on_another_forms_plan() {
		list( $switched, $switched_sub ) = $this->subscribed_customer();
		list( $member ) = $this->subscribed_customer();
		$this->webhook_paying_for( 'price_cheap', 'recurring' )->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $switched_sub, 'price_cheap' ) );

		$check = new \WPUserManager\Stripe\SubscriptionPlanCheck( 'test' );
		delete_option( 'wpum_stripe_disallowed_plan_users_test' );
		$check->scan();

		$affected = $check->get_affected_users();
		$this->assertSame( 'price_cheap', $affected[ $switched->ID ] ?? null );
		$this->assertArrayNotHasKey( $member->ID, $affected );
	}

	public function test_webhooks_keep_the_affected_members_up_to_date() {
		list( $customer, $sub_id ) = $this->subscribed_customer();
		$controller                = $this->webhook_paying_for( 'price_cheap', 'recurring' );
		$check                     = new \WPUserManager\Stripe\SubscriptionPlanCheck( 'test' );

		$controller->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_cheap' ) );
		$this->assertArrayHasKey( $customer->ID, $check->get_affected_users() );

		$controller->handle( 'handleCustomerSubscriptionUpdated', $this->subscription_updated_payload( $sub_id, 'price_premium' ) );
		$this->assertArrayNotHasKey( $customer->ID, $check->get_affected_users() );
	}

	public function test_notice_links_to_the_doc_and_collapses_the_members() {
		list( $customer ) = $this->subscribed_customer();

		$message = wp_kses_post( ( new \WPUserManager\Stripe\SubscriptionPlanCheck( 'test' ) )->get_notice_message( array( $customer->ID => 'price_cheap' ) ) );

		$this->assertStringContainsString( '1 member has', $message );
		$this->assertStringContainsString( 'Learn more', $message );
		$this->assertStringContainsString( '<details', $message, 'The member list should survive wp_kses_post as a collapsed list' );
		$this->assertStringContainsString( 'Show 1 member', $message );
		$this->assertStringContainsString( esc_html( $customer->user_email ), $message );
		$this->assertStringContainsString( 'price_cheap', $message );
		$this->assertStringContainsString( esc_html( $this->registration_form->get_name() ), $message );
		$this->assertStringContainsString( 'wpum_stripe_check_plans', $message );
	}

	/*
	 * Content restricted to a Stripe product.
	 */

	public function test_unpaid_plan_does_not_count_as_purchased() {
		$customer = $this->unpaid_customer();

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->hasPaidByPlan( 'price_premium' ), 'Registering for a plan is not buying it' );
	}

	public function test_paid_plan_counts_as_purchased() {
		$customer = $this->unpaid_customer();
		$this->webhook_paying_for( 'price_premium' )->handle( 'handleCheckoutSessionCompleted', $this->session_payload( $customer->user_email ) );

		$user = new \WPUserManager\Stripe\Models\User( $customer->ID );
		$this->assertTrue( $user->hasPaidByPlan( 'price_premium' ) );
		$this->assertFalse( $user->hasPaidByPlan( 'price_cheap' ) );
	}

	public function test_ended_subscription_does_not_count_as_purchased() {
		$this->require_subscriptions_table();
		$customer = $this->unpaid_customer( 'price_premium', 'recurring' );
		$sub_id   = 'sub_ended_' . wp_rand();

		$subscriptions = new \WPUserManager\Stripe\Controllers\Subscriptions( 'test' );
		$subscriptions->insert( array(
			'user_id'         => $customer->ID,
			'customer_id'     => 'cus_ended',
			'subscription_id' => $sub_id,
			'plan_id'         => 'price_premium',
			'ends_at'         => '2020-01-01 00:00:00',
		) );

		$this->assertFalse( ( new \WPUserManager\Stripe\Models\User( $customer->ID ) )->hasPaidByPlan( 'price_premium' ) );
	}
}
