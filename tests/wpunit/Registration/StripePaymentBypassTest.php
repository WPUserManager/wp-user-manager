<?php
/**
 * Tests for Stripe registration payment bypass vulnerability.
 *
 * Ensures that when a registration form is configured with Stripe products,
 * users cannot register without selecting and paying for a plan.
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class StripePaymentBypassTest extends RegistrationTestCase {

	/**
	 * @var \WPUserManager\Stripe\Registration|null
	 */
	protected $stripe_registration;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Registration' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}
	}

	/**
	 * Test that the injected Stripe plan field is marked as required.
	 */
	public function test_stripe_plan_field_is_required() {
		$fields = array(
			'user_email' => array(
				'label'    => 'Email',
				'type'     => 'email',
				'required' => true,
			),
		);

		$form = $this->create_mock_registration_form_with_stripe();

		$registration = $this->create_stripe_registration();

		$result = $registration->inject_registration_fields( $fields, $form );

		$this->assertArrayHasKey( 'wpum_stripe_plan', $result, 'Stripe plan field should be injected' );
		$this->assertTrue( $result['wpum_stripe_plan']['required'], 'Stripe plan field must be required to prevent payment bypass' );
	}

	/**
	 * Test that the Stripe plan field is NOT injected when no products are configured.
	 */
	public function test_stripe_plan_field_not_injected_without_products() {
		$fields = array(
			'user_email' => array(
				'label'    => 'Email',
				'type'     => 'email',
				'required' => true,
			),
		);

		$form = $this->create_mock_registration_form_without_stripe();

		$registration = $this->create_stripe_registration();

		$result = $registration->inject_registration_fields( $fields, $form );

		$this->assertArrayNotHasKey( 'wpum_stripe_plan', $result, 'Stripe plan field should not be injected when no products are configured' );
	}

	/**
	 * Test that User model treats missing plan metadata correctly for access control.
	 *
	 * This verifies the access control logic that the bypass exploits:
	 * - shouldBeSubscribed() returns false when no product data exists
	 * - isPaid() returns true when no product data exists
	 *
	 * The combination means a user with no plan metadata is treated as "allowed".
	 * The fix prevents users from reaching this state by requiring the plan field.
	 */
	public function test_user_without_plan_meta_access_control_state() {
		if ( ! class_exists( 'WPUserManager\Stripe\Models\User' ) ) {
			$this->markTestSkipped( 'Stripe User model is not available.' );
		}

		$user_id = $this->factory()->user->create();

		// Ensure no Stripe plan metadata exists (simulates the bypass).
		$gateway_mode = wpum_get_option( 'stripe_gateway_mode', 'test' );
		delete_user_meta( $user_id, 'wpum_stripe_plan_' . $gateway_mode );

		$stripe_user = new \WPUserManager\Stripe\Models\User( $user_id );

		// Document the current (dangerous) behavior that the field-required fix prevents.
		$this->assertFalse( $stripe_user->shouldBeSubscribed(), 'User without plan meta: shouldBeSubscribed() returns false' );
		$this->assertTrue( $stripe_user->isPaid(), 'User without plan meta: isPaid() returns true — field validation must prevent this state' );
	}

	/**
	 * Test that the plan field is required with a single Stripe product.
	 */
	public function test_stripe_plan_field_required_single_product() {
		$fields = array();

		$form = $this->create_mock_registration_form_with_stripe( array( 'price_single_1' ) );

		$registration = $this->create_stripe_registration( array(
			array( 'value' => 'price_single_1', 'label' => 'Single Plan ($5)' ),
		) );

		$result = $registration->inject_registration_fields( $fields, $form );

		$this->assertArrayHasKey( 'wpum_stripe_plan', $result );
		$this->assertTrue( $result['wpum_stripe_plan']['required'] );
		$this->assertCount( 1, $result['wpum_stripe_plan']['options'], 'Should have exactly one option' );
	}

	/**
	 * Test that the plan field is required with multiple Stripe products.
	 */
	public function test_stripe_plan_field_required_multiple_products() {
		$fields = array();

		$form = $this->create_mock_registration_form_with_stripe( array( 'price_multi_1', 'price_multi_2', 'price_multi_3' ) );

		$registration = $this->create_stripe_registration( array(
			array( 'value' => 'price_multi_1', 'label' => 'Basic ($5/mo)' ),
			array( 'value' => 'price_multi_2', 'label' => 'Pro ($15/mo)' ),
			array( 'value' => 'price_multi_3', 'label' => 'Enterprise ($50/mo)' ),
		) );

		$result = $registration->inject_registration_fields( $fields, $form );

		$this->assertArrayHasKey( 'wpum_stripe_plan', $result );
		$this->assertTrue( $result['wpum_stripe_plan']['required'] );
		$this->assertCount( 3, $result['wpum_stripe_plan']['options'], 'Should have all three options' );
	}

	/**
	 * Test that only configured products appear in the options (not all available plans).
	 */
	public function test_stripe_plan_options_filtered_to_form_config() {
		$fields = array();

		// Form only has one product configured, but three exist.
		$form = $this->create_mock_registration_form_with_stripe( array( 'price_multi_2' ) );

		$registration = $this->create_stripe_registration( array(
			array( 'value' => 'price_multi_1', 'label' => 'Basic ($5/mo)' ),
			array( 'value' => 'price_multi_2', 'label' => 'Pro ($15/mo)' ),
			array( 'value' => 'price_multi_3', 'label' => 'Enterprise ($50/mo)' ),
		) );

		$result = $registration->inject_registration_fields( $fields, $form );

		$this->assertCount( 1, $result['wpum_stripe_plan']['options'] );
		$this->assertArrayHasKey( 'price_multi_2', $result['wpum_stripe_plan']['options'] );
	}

	/**
	 * Test that save_plan() does not store metadata when wpum_stripe_plan is absent.
	 *
	 * This confirms the mechanism of the bypass: if the POST field is missing,
	 * no plan metadata is stored, leaving the user in the "no metadata = allowed" state.
	 */
	public function test_save_plan_skips_when_post_field_absent() {
		$user_id = $this->factory()->user->create();

		$gateway_mode = wpum_get_option( 'stripe_gateway_mode', 'test' );
		delete_user_meta( $user_id, 'wpum_stripe_plan_' . $gateway_mode );

		// Simulate POST without wpum_stripe_plan.
		unset( $_POST['wpum_stripe_plan'] );

		$registration = $this->create_stripe_registration();
		$registration->save_plan( $user_id );

		$plan_meta = get_user_meta( $user_id, 'wpum_stripe_plan_' . $gateway_mode, true );
		$this->assertEmpty( $plan_meta, 'No plan metadata should be stored when POST field is absent' );
	}

	/**
	 * Create a mock registration form that has Stripe products configured.
	 *
	 * @param array $price_ids Stripe price IDs to configure.
	 * @return \WPUM_Registration_Form|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function create_mock_registration_form_with_stripe( $price_ids = array( 'price_test_123' ) ) {
		$form = $this->createMock( \WPUM_Registration_Form::class );
		$form->method( 'get_setting' )
			->willReturnCallback( function ( $key ) use ( $price_ids ) {
				if ( 'stripe_plan_id' === $key ) {
					return $price_ids;
				}
				return null;
			} );

		return $form;
	}

	/**
	 * Create a mock registration form without Stripe products.
	 *
	 * @return \WPUM_Registration_Form|\PHPUnit\Framework\MockObject\MockObject
	 */
	protected function create_mock_registration_form_without_stripe() {
		$form = $this->createMock( \WPUM_Registration_Form::class );
		$form->method( 'get_setting' )
			->willReturnCallback( function ( $key ) {
				if ( 'stripe_plan_id' === $key ) {
					return array();
				}
				return null;
			} );

		return $form;
	}

	/**
	 * Create a Stripe Registration instance with mock dependencies.
	 *
	 * @param array|null $plans Available plans. Defaults to a single test plan.
	 * @return \WPUserManager\Stripe\Registration
	 */
	protected function create_stripe_registration( $plans = null ) {
		if ( null === $plans ) {
			$plans = array(
				array( 'value' => 'price_test_123', 'label' => 'Test Plan ($10/mo)' ),
			);
		}

		$products = $this->createMock( \WPUserManager\Stripe\Controllers\Products::class );
		$products->method( 'get_plans' )
			->willReturn( $plans );

		$billing = $this->createMock( \WPUserManager\Stripe\Billing::class );

		return new \WPUserManager\Stripe\Registration(
			'pk_test_xxx',
			'sk_test_xxx',
			true,
			$billing,
			$products
		);
	}
}
