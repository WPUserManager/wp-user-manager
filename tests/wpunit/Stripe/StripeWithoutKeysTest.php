<?php
/**
 * With no Stripe keys for the current mode (disconnected, never connected, or
 * switched to a mode without keys), accounts that owe a payment stay restricted
 * and a form with Stripe plans doesn't hand out its role without payment.
 */

require_once dirname( __DIR__ ) . '/Registration/RegistrationTestCase.php';

class StripeWithoutKeysTest extends RegistrationTestCase {

	/**
	 * @var \WPUserManager\Stripe\Stripe
	 */
	protected $stripe;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Stripe' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		foreach ( array( 'test', 'live' ) as $mode ) {
			wpum_delete_option( $mode . '_stripe_publishable_key' );
			wpum_delete_option( $mode . '_stripe_secret_key' );
			wpum_delete_option( $mode . '_stripe_webhook_secret' );
		}
		wpum_update_option( 'stripe_gateway_mode', 'test' );

		$cache = new \ReflectionProperty( \WPUserManager\Stripe\Account::class, 'owes_payment' );
		$cache->setAccessible( true );
		$cache->setValue( null, array() );

		$this->stripe = new \WPUserManager\Stripe\Stripe();
		$this->stripe->init();
	}

	public function _tearDown() {
		if ( $this->default_form ) {
			$this->default_form->delete_meta( 'stripe_plan_id', '' );
		}

		wpum_delete_option( 'stripe_gateway_mode' );

		parent::_tearDown();
	}

	protected function user_with_plan( $role, $paid, $mode = 'test' ) {
		$user_id = $this->factory()->user->create( array( 'role' => $role ) );
		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );

		$product = new \WPUserManager\Stripe\Models\Product( 'price_test', array( 'name' => 'Plan' ), array( 'type' => 'one_time', 'unit_amount' => 1000 ) );
		if ( $paid ) {
			$product->setPaid();
		}
		update_user_meta( $user_id, 'wpum_stripe_plan_' . $mode, $product->to_array() );

		return $user_id;
	}

	public function test_unpaid_member_can_only_read_without_stripe_keys() {
		$user_id = $this->user_with_plan( 'editor', false );

		$this->assertTrue( user_can( $user_id, 'read' ) );
		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );
		$this->assertFalse( user_can( $user_id, 'edit_others_posts' ) );
	}

	public function test_unpaid_member_with_manage_options_role_is_restricted_without_stripe_keys() {
		add_role( 'wpum_test_manager', 'Manager', array( 'read' => true, 'manage_options' => true, 'create_users' => true, 'promote_users' => true ) );
		$user_id = $this->user_with_plan( 'wpum_test_manager', false );

		$this->assertFalse( user_can( $user_id, 'manage_options' ) );
		$this->assertFalse( user_can( $user_id, 'create_users' ) );

		remove_role( 'wpum_test_manager' );
	}

	public function test_unpaid_member_has_no_access_roles_without_stripe_keys() {
		$user_id = $this->user_with_plan( 'editor', false );

		$this->assertSame( array(), wpum_get_user_access_roles( get_user_by( 'id', $user_id ) ) );
	}

	public function test_paid_member_keeps_role_without_stripe_keys() {
		$user_id = $this->user_with_plan( 'editor', true );

		$this->assertTrue( user_can( $user_id, 'edit_others_posts' ) );
	}

	public function test_member_without_a_plan_is_unaffected_without_stripe_keys() {
		$user_id = $this->factory()->user->create( array( 'role' => 'editor' ) );

		$this->assertTrue( user_can( $user_id, 'edit_others_posts' ) );
		$this->assertContains( 'editor', wpum_get_user_access_roles( get_user_by( 'id', $user_id ) ) );
	}

	/**
	 * Switching to a mode with no keys used to drop the restriction too.
	 */
	public function test_unpaid_member_stays_restricted_after_switching_mode() {
		$user_id = $this->user_with_plan( 'editor', false, 'live' );
		wpum_update_option( 'stripe_gateway_mode', 'live' );
		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );

		wpum_update_option( 'stripe_gateway_mode', 'test' );
		$cache = new \ReflectionProperty( \WPUserManager\Stripe\Account::class, 'owes_payment' );
		$cache->setAccessible( true );
		$cache->setValue( null, array() );

		$this->assertFalse( user_can( $user_id, 'edit_posts' ) );
	}

	/**
	 * WPScan: a published paid form kept assigning its role once Stripe was disconnected.
	 */
	public function test_form_with_plans_refuses_registration_without_stripe_keys() {
		$this->default_form->update_meta( 'stripe_plan_id', array( 'price_test' ) );

		$data = $this->get_valid_registration_data();
		$this->submit_registration( $data );

		$this->assertFalse( get_user_by( 'email', $data['register']['user_email'] ), 'A paid form must not register users while Stripe is unavailable' );
	}

	public function test_form_without_plans_still_registers_without_stripe_keys() {
		$data = $this->get_valid_registration_data();
		$this->submit_registration( $data );

		$this->assertInstanceOf( \WP_User::class, get_user_by( 'email', $data['register']['user_email'] ) );
	}

	public function test_saved_plans_stay_editable_without_stripe_keys() {
		$this->default_form->update_meta( 'stripe_plan_id', array( 'price_test' ) );

		$sections = $this->stripe->register_registration_settings_tab( array() );
		$settings = $this->stripe->register_saved_plans_setting( array() );

		$this->assertArrayHasKey( 'payment', $sections['registration'] );
		$this->assertSame( 'stripe_plan_id', $settings['payment'][0]['id'] );
		$this->assertSame( array( array( 'value' => 'price_test', 'label' => 'price_test' ) ), $settings['payment'][0]['options'] );
	}

	public function test_no_payment_settings_without_saved_plans() {
		$this->assertSame( array(), $this->stripe->register_registration_settings_tab( array() ) );
		$this->assertSame( array(), $this->stripe->register_saved_plans_setting( array() ) );
	}
}
