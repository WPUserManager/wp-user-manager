<?php
/**
 * Tests for the fixes raised by the WordPress.org Plugin Directory
 * automated security review of 2.9.19.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class PluginDirectoryReviewTest extends WPUMTestCase {

	/**
	 * Call a protected method on an object.
	 *
	 * @param object $object
	 * @param string $method
	 * @param array  $args
	 *
	 * @return mixed
	 */
	protected function call_protected( $object, $method, $args = array() ) {
		$ref = new \ReflectionMethod( $object, $method );
		$ref->setAccessible( true );

		return $ref->invokeArgs( $object, $args );
	}

	/**
	 * Set a protected property on an object.
	 *
	 * @param object $object
	 * @param string $property
	 * @param mixed  $value
	 */
	protected function set_protected( $object, $property, $value ) {
		$ref = new \ReflectionProperty( $object, $property );
		$ref->setAccessible( true );
		$ref->setValue( $object, $value );
	}

	/*
	 * Addon check: deactivation requires activate_plugins.
	 */

	/**
	 * @var string[] Plugins deactivate_plugins() was asked to deactivate.
	 */
	protected $deactivated = array();

	protected function activate_fake_addon() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		// The test bootstrap controls active_plugins, so force the fake addon active.
		add_filter( 'pre_option_active_plugins', function () {
			return array( 'wpum-fake-addon/wpum-fake-addon.php' );
		}, 999 );

		$this->deactivated = array();
		add_action( 'deactivate_plugin', function ( $plugin ) {
			$this->deactivated[] = $plugin;
		} );

		return new WPUM_Addon_Check( array(
			'title'       => 'Fake Addon',
			'min_version' => '99.0',
			'file'        => WP_PLUGIN_DIR . '/wpum-fake-addon/wpum-fake-addon.php',
		) );
	}

	public function test_subscriber_cannot_trigger_addon_deactivation() {
		$check = $this->activate_fake_addon();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$check->deactivate();

		$this->assertNotContains( 'wpum-fake-addon/wpum-fake-addon.php', $this->deactivated, 'A subscriber page view must not deactivate an addon' );
	}

	public function test_admin_visit_deactivates_outdated_addon() {
		$check = $this->activate_fake_addon();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		if ( is_multisite() ) {
			grant_super_admin( get_current_user_id() );
		}

		$check->deactivate();

		$this->assertContains( 'wpum-fake-addon/wpum-fake-addon.php', $this->deactivated );
	}

	/*
	 * Stripe webhook: one time plans are only marked paid once Stripe confirms payment.
	 */

	protected function create_webhook_controller() {
		$ref        = new \ReflectionClass( \WPUserManager\Stripe\StripeWebhookController::class );
		$controller = $ref->newInstanceWithoutConstructor();

		$subscriptions = $this->createMock( \WPUserManager\Stripe\Controllers\Subscriptions::class );
		$subscriptions->method( 'where' )->willReturn( null );
		$this->set_protected( $controller, 'subscriptions', $subscriptions );

		return $controller;
	}

	protected function create_user_with_unpaid_plan() {
		$user_id = $this->factory()->user->create( array( 'user_email' => 'buyer' . wp_rand() . '@example.com' ) );

		$user = new \WPUserManager\Stripe\Models\User( $user_id );
		$user->setPlanMeta( ( new \WPUserManager\Stripe\Models\Product() )->to_array() );

		return get_userdata( $user_id );
	}

	protected function session_payload( $type, $email, $payment_status ) {
		return array(
			'type' => $type,
			'data' => array(
				'object' => array(
					'customer_email' => $email,
					'customer'       => 'cus_test',
					'subscription'   => null,
					'payment_status' => $payment_status,
				),
			),
		);
	}

	protected function plan_is_paid( $user_id ) {
		$meta = ( new \WPUserManager\Stripe\Models\User( $user_id ) )->getPlanMeta();

		return ! empty( $meta['paid'] );
	}

	public function test_unpaid_checkout_session_does_not_mark_plan_paid() {
		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$user    = $this->create_user_with_unpaid_plan();
		$payload = $this->session_payload( 'checkout.session.completed', $user->user_email, 'unpaid' );

		$this->call_protected( $this->create_webhook_controller(), 'handleCheckoutSessionCompleted', array( $payload ) );

		$this->assertFalse( $this->plan_is_paid( $user->ID ), 'An unpaid Checkout Session must not mark the plan as paid' );
	}

	public function test_paid_checkout_session_marks_plan_paid() {
		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$user    = $this->create_user_with_unpaid_plan();
		$payload = $this->session_payload( 'checkout.session.completed', $user->user_email, 'paid' );

		$this->call_protected( $this->create_webhook_controller(), 'handleCheckoutSessionCompleted', array( $payload ) );

		$this->assertTrue( $this->plan_is_paid( $user->ID ) );
	}

	public function test_async_payment_succeeded_marks_plan_paid() {
		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$user    = $this->create_user_with_unpaid_plan();
		$payload = $this->session_payload( 'checkout.session.async_payment_succeeded', $user->user_email, 'paid' );

		$this->call_protected( $this->create_webhook_controller(), 'handleCheckoutSessionAsyncPaymentSucceeded', array( $payload ) );

		$this->assertTrue( $this->plan_is_paid( $user->ID ), 'A settled delayed payment should mark the plan as paid' );
	}

	public function test_async_payment_handler_ignores_unpaid_sessions() {
		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$user    = $this->create_user_with_unpaid_plan();
		$payload = $this->session_payload( 'checkout.session.async_payment_succeeded', $user->user_email, 'unpaid' );

		$this->call_protected( $this->create_webhook_controller(), 'handleCheckoutSessionAsyncPaymentSucceeded', array( $payload ) );

		$this->assertFalse( $this->plan_is_paid( $user->ID ) );
	}

	/*
	 * Subscriptions::where() with an empty value must not match another user's subscription.
	 */

	protected function create_unrelated_subscription( $mode = 'test' ) {
		global $wpdb;

		$subscriptions = new \WPUserManager\Stripe\Controllers\Subscriptions( $mode );
		$table         = $wpdb->prefix . 'wpum_stripe_subscriptions';
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
			$this->markTestSkipped( 'Stripe subscriptions table is not installed.' );
		}

		$subscriptions->insert( array(
			'user_id'         => $this->factory()->user->create(),
			'customer_id'     => 'cus_someone_else',
			'subscription_id' => 'sub_someone_else',
			'plan_id'         => 'price_monthly',
		) );

		return $subscriptions;
	}

	public function test_subscription_where_with_empty_value_matches_nothing() {
		$subscriptions = $this->create_unrelated_subscription();

		$this->assertNull( $subscriptions->where( 'subscription_id', null ) );
		$this->assertNull( $subscriptions->where( 'customer_id', '' ) );
		$this->assertNotNull( $subscriptions->where( 'subscription_id', 'sub_someone_else' ), 'A real ID still matches' );
	}

	public function test_one_time_payment_marked_paid_when_other_subscriptions_exist() {
		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$subscriptions = $this->create_unrelated_subscription();

		$ref        = new \ReflectionClass( \WPUserManager\Stripe\StripeWebhookController::class );
		$controller = $ref->newInstanceWithoutConstructor();
		$this->set_protected( $controller, 'subscriptions', $subscriptions );

		$user    = $this->create_user_with_unpaid_plan();
		$payload = $this->session_payload( 'checkout.session.completed', $user->user_email, 'paid' );

		$this->call_protected( $controller, 'handleCheckoutSessionCompleted', array( $payload ) );

		$this->assertTrue( $this->plan_is_paid( $user->ID ), 'Another user\'s subscription must not stop a one-time payment being recorded' );
	}

	/*
	 * Stripe registration: submitted price IDs must be configured on the form.
	 */

	protected function create_posted_form( $price_ids ) {
		$registration_form = $this->createMock( \WPUM_Registration_Form::class );
		$registration_form->method( 'get_setting' )->willReturnCallback( function ( $key ) use ( $price_ids ) {
			return 'stripe_plan_id' === $key ? $price_ids : null;
		} );

		return new class( $registration_form ) {
			private $registration_form;

			public function __construct( $registration_form ) {
				$this->registration_form = $registration_form;
			}

			public function get_registration_form() {
				return $this->registration_form;
			}
		};
	}

	protected function create_stripe_registration() {
		$ref = new \ReflectionClass( \WPUserManager\Stripe\Registration::class );

		return $ref->newInstanceWithoutConstructor();
	}

	public function test_configured_price_id_is_allowed() {
		if ( ! class_exists( 'WPUserManager\Stripe\Registration' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$form = $this->create_posted_form( array( 'price_basic', 'price_pro' ) );

		$this->assertTrue( $this->call_protected( $this->create_stripe_registration(), 'is_plan_allowed_for_form', array( 'price_pro', $form ) ) );
	}

	public function test_unconfigured_price_id_is_rejected() {
		if ( ! class_exists( 'WPUserManager\Stripe\Registration' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$form = $this->create_posted_form( array( 'price_basic' ) );

		$this->assertFalse( $this->call_protected( $this->create_stripe_registration(), 'is_plan_allowed_for_form', array( 'price_other_product', $form ) ) );
	}

	public function test_price_id_rejected_when_form_has_no_plans() {
		if ( ! class_exists( 'WPUserManager\Stripe\Registration' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$form = $this->create_posted_form( null );

		$this->assertFalse( $this->call_protected( $this->create_stripe_registration(), 'is_plan_allowed_for_form', array( 'price_basic', $form ) ) );
	}

	/*
	 * Stripe Connect: the callback must match a state issued to the same administrator.
	 */

	protected function create_admin() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		return $admin_id;
	}

	public function test_connect_accepts_state_issued_to_current_admin() {
		$this->create_admin();
		$connect = new \WPUserManager\Stripe\Connect();
		$state   = $this->call_protected( $connect, 'get_state', array( true ) );

		$this->assertTrue( $this->call_protected( $connect, 'consume_state', array( $state ) ) );
	}

	public function test_connect_state_is_single_use() {
		$this->create_admin();
		$connect = new \WPUserManager\Stripe\Connect();
		$state   = $this->call_protected( $connect, 'get_state', array( true ) );

		$this->call_protected( $connect, 'consume_state', array( $state ) );

		$this->assertFalse( $this->call_protected( $connect, 'consume_state', array( $state ) ), 'A state must not be accepted twice' );
	}

	public function test_connect_rejects_state_not_issued_by_site() {
		$this->create_admin();
		$connect = new \WPUserManager\Stripe\Connect();
		$this->call_protected( $connect, 'get_state', array( true ) );

		$forged = base64_encode( serialize( array( 'test_mode' => 1, 'site_id' => '123', 'site_url' => 'https://attacker.test' ) ) ); // phpcs:ignore

		$this->assertFalse( $this->call_protected( $connect, 'consume_state', array( $forged ) ) );
	}

	public function test_connect_rejects_state_issued_to_another_admin() {
		$this->create_admin();
		$connect = new \WPUserManager\Stripe\Connect();
		$state   = $this->call_protected( $connect, 'get_state', array( true ) );

		$this->create_admin();

		$this->assertFalse( $this->call_protected( $connect, 'consume_state', array( $state ) ), 'A state must be bound to the admin who started the connection' );
	}

	public function test_connect_accepts_state_with_plus_decoded_as_space() {
		$this->create_admin();
		$connect = new \WPUserManager\Stripe\Connect();
		$state   = 'abc+def+ghi=';
		$this->call_protected( $connect, 'remember_state', array( $state ) );

		$this->assertTrue( $this->call_protected( $connect, 'consume_state', array( 'abc def ghi=' ) ) );
	}

	/*
	 * License requests verify TLS certificates.
	 */

	public function test_license_api_request_verifies_tls() {
		$ref     = new \ReflectionClass( WPUM_License::class );
		$license = $ref->newInstanceWithoutConstructor();
		$this->set_protected( $license, 'api_url', 'https://wpusermanager.com' );
		$this->set_protected( $license, 'item_id', 1 );

		$captured = null;
		$capture  = function ( $pre, $args ) use ( &$captured ) {
			$captured = $args;

			return array(
				'headers'  => array(),
				'body'     => '{}',
				'response' => array( 'code' => 200, 'message' => 'OK' ),
				'cookies'  => array(),
			);
		};
		add_filter( 'pre_http_request', $capture, 10, 2 );

		$this->call_protected( $license, 'api_request', array( 'check_license', 'key', home_url() ) );

		remove_filter( 'pre_http_request', $capture, 10 );

		$this->assertNotNull( $captured );
		$this->assertTrue( $captured['sslverify'], 'License requests must verify TLS certificates' );
	}

	/*
	 * File field output escapes URLs.
	 */

	public function test_file_field_output_escapes_image_url() {
		$ref   = new \ReflectionClass( WPUM_Field_File::class );
		$field = $ref->newInstanceWithoutConstructor();

		$output = $field->get_formatted_output( null, 'https://example.com/a.png" onerror="alert(1)' );

		$this->assertStringNotContainsString( '" onerror="', $output );
	}

	public function test_file_field_output_escapes_link_url() {
		$ref   = new \ReflectionClass( WPUM_Field_File::class );
		$field = $ref->newInstanceWithoutConstructor();

		$output = $field->get_formatted_output( null, 'https://example.com/a.pdf" onclick="alert(1)' );

		$this->assertStringNotContainsString( '" onclick="', $output );
	}
}
