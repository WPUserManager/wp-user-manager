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
