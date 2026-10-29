<?php
/**
 * Stripe event destinations default to the Thin payload format, which only
 * references what changed and has no data.object. The webhook handler can't use
 * those events, so it records them and the settings screen tells the admin to
 * recreate the webhook with the Snapshot format.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/528
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class WebhookThinPayloadTest extends WPUMTestCase {

	const SECRET = 'whsec_test_secret';

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\StripeWebhookController' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		\WPUserManager\Stripe\StripeWebhookController::clear_thin_event( 'test' );
	}

	public function _tearDown() {
		\WPUserManager\Stripe\StripeWebhookController::clear_thin_event( 'test' );
		\WPUserManager\Stripe\StripeWebhookController::clear_thin_event( 'live' );
		set_current_screen( 'front' );

		parent::_tearDown();
	}

	/**
	 * A webhook request signed as Stripe signs it.
	 *
	 * @param array  $payload
	 * @param string $secret
	 *
	 * @return WP_REST_Request
	 */
	protected function webhook_request( $payload, $secret = self::SECRET ) {
		$body      = wp_json_encode( $payload );
		$timestamp = time();
		$signature = hash_hmac( 'sha256', $timestamp . '.' . $body, $secret );

		$request = new WP_REST_Request( 'POST', '/wpum/v1/stripe/webhook' );
		$request->set_body( $body );
		$request->set_header( 'Stripe-Signature', 't=' . $timestamp . ',v1=' . $signature );

		return $request;
	}

	protected function controller() {
		return new \WPUserManager\Stripe\StripeWebhookController( 'sk_test_fake', self::SECRET, 'test' );
	}

	protected function thin_event() {
		return array(
			'id'             => 'evt_test_thin',
			'object'         => 'v2.core.event',
			'type'           => 'v1.customer.subscription.updated',
			'livemode'       => false,
			'created'        => gmdate( 'c' ),
			'related_object' => array(
				'id'   => 'sub_123',
				'type' => 'subscription',
				'url'  => '/v1/subscriptions/sub_123',
			),
		);
	}

	protected function snapshot_event() {
		return array(
			'id'     => 'evt_test_snapshot',
			'object' => 'event',
			'type'   => 'customer.created',
			'data'   => array(
				'object' => array(
					'id'     => 'cus_123',
					'object' => 'customer',
				),
			),
		);
	}

	public function test_thin_event_is_recorded_and_acknowledged() {
		$response = $this->controller()->handleWebhook( $this->webhook_request( $this->thin_event() ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringContainsString( 'Snapshot', $response->get_data() );

		$event = \WPUserManager\Stripe\StripeWebhookController::last_thin_event( 'test' );
		$this->assertIsArray( $event );
		$this->assertSame( 'v1.customer.subscription.updated', $event['type'] );
		$this->assertFalse( \WPUserManager\Stripe\StripeWebhookController::last_thin_event( 'live' ) );
	}

	public function test_an_unsigned_thin_event_is_not_recorded() {
		$this->controller()->handleWebhook( $this->webhook_request( $this->thin_event(), 'whsec_wrong' ) );

		$this->assertFalse( \WPUserManager\Stripe\StripeWebhookController::last_thin_event( 'test' ) );
	}

	public function test_a_snapshot_event_clears_the_record() {
		$this->controller()->handleWebhook( $this->webhook_request( $this->thin_event() ) );
		$this->controller()->handleWebhook( $this->webhook_request( $this->snapshot_event() ) );

		$this->assertFalse( \WPUserManager\Stripe\StripeWebhookController::last_thin_event( 'test' ) );
	}

	public function test_settings_screen_warns_while_thin_events_arrive() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		set_current_screen( 'users_page_wpum-settings' );
		$settings = new \WPUserManager\Stripe\Settings( new \WPUserManager\Stripe\Connect() );

		ob_start();
		$settings->thin_webhook_notice();
		$this->assertSame( '', ob_get_clean(), 'No notice before any Thin event.' );

		\WPUserManager\Stripe\StripeWebhookController::record_thin_event( 'test', 'v1.invoice.paid' );

		ob_start();
		$settings->thin_webhook_notice();
		$notice = ob_get_clean();

		$this->assertStringContainsString( 'notice-error', $notice );
		$this->assertStringContainsString( 'Snapshot', $notice );
		$this->assertStringContainsString( 'v1.invoice.paid', $notice );
	}

	public function test_webhook_setting_explains_snapshot_and_lists_the_events() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$settings = new \WPUserManager\Stripe\Settings( new \WPUserManager\Stripe\Connect() );

		$json = wp_json_encode( $settings->register_settings( array() ) );

		$this->assertStringContainsString( 'Snapshot', $json );
		foreach ( \WPUserManager\Stripe\Settings::webhook_events() as $event ) {
			$this->assertStringContainsString( $event, $json );
		}
	}
}
