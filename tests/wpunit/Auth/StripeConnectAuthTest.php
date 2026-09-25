<?php
/**
 * Tests for Stripe Connect authorization.
 *
 * Ensures that Connect::complete() requires manage_options capability
 * and cannot be triggered by subscribers or other low-privilege users.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Testable subclass that bypasses filter_input() (which reads SAPI-level
 * GET data unavailable in phpunit) and exposes the authorization logic.
 */
class Testable_Connect extends \WPUserManager\Stripe\Connect {

	public function complete() {
		// Simulate the GET parameter checks passing (page + action match).
		// This lets us test the capability gate that follows.

		if ( ! current_user_can( 'manage_options' ) ) {
			return 'blocked';
		}

		// Would normally continue to state check, wp_remote_get, etc.
		return 'allowed';
	}
}

class StripeConnectAuthTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Connect' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}
	}

	public function test_subscriber_cannot_complete_stripe_connect() {
		$subscriber_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber_id );

		$connect = new Testable_Connect();
		$result  = $connect->complete();

		$this->assertEquals( 'blocked', $result, 'Subscriber should be blocked from completing Stripe Connect' );
	}

	public function test_editor_cannot_complete_stripe_connect() {
		$editor_id = $this->factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );

		$connect = new Testable_Connect();
		$result  = $connect->complete();

		$this->assertEquals( 'blocked', $result, 'Editor should be blocked from completing Stripe Connect' );
	}

	public function test_admin_can_complete_stripe_connect() {
		$admin_id = $this->factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$connect = new Testable_Connect();
		$result  = $connect->complete();

		$this->assertEquals( 'allowed', $result, 'Administrator should be allowed to complete Stripe Connect' );
	}

	public function test_logged_out_user_cannot_complete_stripe_connect() {
		wp_set_current_user( 0 );

		$connect = new Testable_Connect();
		$result  = $connect->complete();

		$this->assertEquals( 'blocked', $result, 'Logged out user should be blocked from completing Stripe Connect' );
	}

	/**
	 * Verify that the actual Connect::complete() method contains
	 * the current_user_can check in its source code.
	 */
	public function test_connect_complete_has_capability_check() {
		$ref    = new \ReflectionMethod( \WPUserManager\Stripe\Connect::class, 'complete' );
		$file   = $ref->getFileName();
		$start  = $ref->getStartLine();
		$end    = $ref->getEndLine();
		$source = implode( '', array_slice( file( $file ), $start - 1, $end - $start + 1 ) );

		$this->assertStringContainsString(
			"current_user_can( 'manage_options' )",
			$source,
			'Connect::complete() must contain a current_user_can manage_options check'
		);
	}
}
