<?php
/**
 * Tests that a Stripe registration only accepts the plans configured on the
 * form, and never leaves an account without plan data (Patchstack follow-up
 * to the 2.9.19 payment bypass fix).
 */

require_once __DIR__ . '/RegistrationTestCase.php';

if ( class_exists( 'WPUserManager\Stripe\Controllers\Products' ) && ! class_exists( 'Fixture_Stripe_Products' ) ) {
	/**
	 * Products controller backed by a fixed catalogue instead of the Stripe API.
	 */
	class Fixture_Stripe_Products extends \WPUserManager\Stripe\Controllers\Products {

		public function __construct() {
			$this->gateway_mode = 'test';
			$this->products     = $this->all();
		}

		public function all( $force = false ) {
			return array(
				'prod_basic' => array(
					'name'   => 'Basic',
					'prices' => array(
						'price_basic' => array( 'type' => 'one_time', 'unit_amount' => 5000, 'currency' => 'usd' ),
					),
				),
				'prod_cheap' => array(
					'name'   => 'Cheap',
					'prices' => array(
						'price_cheap' => array( 'type' => 'one_time', 'unit_amount' => 100, 'currency' => 'usd' ),
					),
				),
			);
		}
	}
}

class StripePlanAllowlistTest extends RegistrationTestCase {

	/**
	 * @var \WPUserManager\Stripe\Registration
	 */
	protected $stripe_registration;

	/**
	 * @var \WPUM_Registration_Form
	 */
	protected $registration_form;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Registration' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}

		$forms                   = WPUM()->registration_forms->get_forms();
		$this->registration_form = $forms[0];
		$this->registration_form->update_meta( 'stripe_plan_id', array( 'price_basic' ) );

		$this->stripe_registration = new \WPUserManager\Stripe\Registration(
			'pk_test_xxx',
			'sk_test_xxx',
			true,
			$this->createMock( \WPUserManager\Stripe\Billing::class ),
			new Fixture_Stripe_Products()
		);
		$this->stripe_registration->init();

		$this->reset_registration_form_cache();
	}

	public function _tearDown() {
		$r = $this->stripe_registration;
		if ( $r ) {
			remove_filter( 'wpum_get_registration_fields', array( $r, 'inject_registration_fields' ), 10 );
			remove_filter( 'submit_wpum_form_validate_fields', array( $r, 'validate_plan' ), 20 );
			remove_action( 'user_register', array( $r, 'record_plan_on_user_creation' ) );
			remove_action( 'wpum_before_registration_end', array( $r, 'save_plan_after_registration' ), 10 );
			remove_action( 'wpum_after_existing_registration', array( $r, 'save_plan' ), 10 );
		}

		if ( $this->registration_form ) {
			$this->registration_form->delete_meta( 'stripe_plan_id', '' );
		}

		$this->reset_registration_form_cache();

		parent::_tearDown();
	}

	/**
	 * The registration form singleton caches its fields; clear them so the
	 * Stripe plan field is injected for this test.
	 */
	protected function reset_registration_form_cache() {
		$form = \WPUM_Form_Registration::instance();
		foreach ( array( 'fields', 'registration_form' ) as $property ) {
			if ( property_exists( $form, $property ) ) {
				$ref = new \ReflectionProperty( $form, $property );
				$ref->setAccessible( true );
				$ref->setValue( $form, null );
			}
		}
	}

	protected function register_with_plan( $plan_id ) {
		$data  = $this->get_valid_registration_data( array( 'register' => array( 'wpum_stripe_plan' => $plan_id ) ) );
		$email = $data['register']['user_email'];

		$this->submit_registration( $data );

		return get_user_by( 'email', $email );
	}

	/**
	 * Patchstack: POSTing wpum_stripe_plan=x created the account, then fataled
	 * in save_plan() leaving it with no plan metadata and full access.
	 */
	public function test_junk_plan_id_does_not_create_account() {
		$user = $this->register_with_plan( 'x' );

		$this->assertFalse( $user, 'A junk plan ID must be rejected before the account is created' );
	}

	/**
	 * Patchstack: get_by_plan() accepted any active price on the Stripe account,
	 * so a cheaper price not offered on the form was accepted.
	 */
	public function test_price_not_configured_on_form_does_not_create_account() {
		$user = $this->register_with_plan( 'price_cheap' );

		$this->assertFalse( $user, 'A real price that this form does not offer must be rejected' );
	}

	public function test_configured_plan_creates_account_with_unpaid_plan() {
		$user = $this->register_with_plan( 'price_basic' );

		$this->assertInstanceOf( \WP_User::class, $user );

		$stripe_user = new \WPUserManager\Stripe\Models\User( $user->ID );
		$meta        = $stripe_user->getPlanMeta();

		$this->assertNotEmpty( $meta, 'Plan metadata must be recorded' );
		$this->assertSame( 'price_basic', $meta['id'] );
		$this->assertEmpty( $meta['paid'] );
		$this->assertFalse( $stripe_user->isPaid(), 'A new account must not be paid until Stripe confirms payment' );
	}

	public function test_get_by_plan_respects_allowlist() {
		$products = new Fixture_Stripe_Products();

		$this->assertFalse( $products->get_by_plan( 'price_cheap', array( 'price_basic' ) ) );
		$this->assertNotFalse( $products->get_by_plan( 'price_basic', array( 'price_basic' ) ) );
		$this->assertNotFalse( $products->get_by_plan( 'price_cheap' ), 'Without an allowlist any active price resolves, as before' );
	}

	/**
	 * Patchstack: a user with no plan metadata was treated as paid.
	 */
	public function test_paid_form_registrant_without_plan_meta_is_not_paid() {
		$user_id = $this->factory()->user->create();
		update_user_meta( $user_id, 'wpum_stripe_payment_required', 1 );

		$stripe_user = new \WPUserManager\Stripe\Models\User( $user_id );

		$this->assertFalse( $stripe_user->isPaid() );
	}

	public function test_existing_member_without_marker_keeps_access() {
		$user_id = $this->factory()->user->create();

		$stripe_user = new \WPUserManager\Stripe\Models\User( $user_id );

		$this->assertTrue( $stripe_user->isPaid(), 'Members from before this change must not be locked out' );
	}

	public function test_save_plan_ignores_unvalidated_junk_without_fatal() {
		$user_id                  = $this->factory()->user->create();
		$_POST['wpum_stripe_plan'] = 'x';

		$this->stripe_registration->save_plan( $user_id );

		$stripe_user = new \WPUserManager\Stripe\Models\User( $user_id );
		$this->assertEmpty( $stripe_user->getPlanMeta() );
	}
}
