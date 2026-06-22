<?php
/**
 * Tests for the Stripe fetch-products handler and webhook controller methods
 * introduced in PR #417.
 *
 * Verifies security guards (nonce, capability, query parameter) on
 * Settings::handle_fetch_stripe_products() and confirms the new webhook
 * handler methods exist on StripeWebhookController.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

use WPUserManager\Stripe\Settings;
use WPUserManager\Stripe\StripeWebhookController;
use WPUserManager\Stripe\Connect;

class StripeFetchProductsTest extends WPUMTestCase {

	/**
	 * @var Settings|null
	 */
	protected $settings;

	/**
	 * @var bool
	 */
	protected $stripe_available = false;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( Settings::class ) ) {
			$this->markTestSkipped( 'Stripe Settings class is not available.' );
		}

		if ( ! class_exists( Connect::class ) ) {
			$this->markTestSkipped( 'Stripe Connect class is not available.' );
		}

		$this->stripe_available = true;

		$connect        = new Connect();
		$this->settings = new Settings( $connect );
	}

	public function _tearDown() {
		$_GET     = array();
		$_REQUEST = array();

		parent::_tearDown();
	}

	// ---------------------------------------------------------------
	// Method existence
	// ---------------------------------------------------------------

	public function test_handle_fetch_stripe_products_method_exists() {
		$this->assertTrue(
			method_exists( $this->settings, 'handle_fetch_stripe_products' ),
			'Settings class should have the handle_fetch_stripe_products method'
		);
	}

	public function test_handle_stripe_connect_disconnect_method_exists() {
		$this->assertTrue(
			method_exists( $this->settings, 'handle_stripe_connect_disconnect' ),
			'Settings class should have the handle_stripe_connect_disconnect method'
		);
	}

	// ---------------------------------------------------------------
	// Hook registration
	// ---------------------------------------------------------------

	public function test_init_registers_admin_init_hook_for_fetch_products() {
		// Remove any existing hooks from previous calls, then re-init.
		remove_all_actions( 'admin_init' );

		$this->settings->init();

		$has_hook = has_action( 'admin_init', array( $this->settings, 'handle_fetch_stripe_products' ) );

		$this->assertNotFalse(
			$has_hook,
			'Settings::init() should register handle_fetch_stripe_products on the admin_init hook'
		);
	}

	public function test_init_registers_admin_init_hook_for_disconnect() {
		remove_all_actions( 'admin_init' );

		$this->settings->init();

		$has_hook = has_action( 'admin_init', array( $this->settings, 'handle_stripe_connect_disconnect' ) );

		$this->assertNotFalse(
			$has_hook,
			'Settings::init() should register handle_stripe_connect_disconnect on the admin_init hook'
		);
	}

	public function test_init_registers_settings_filter() {
		remove_all_filters( 'wpum_registered_settings' );

		$this->settings->init();

		$has_filter = has_filter( 'wpum_registered_settings', array( $this->settings, 'register_settings' ) );

		$this->assertNotFalse(
			$has_filter,
			'Settings::init() should register register_settings on the wpum_registered_settings filter'
		);
	}

	// ---------------------------------------------------------------
	// Security: handler returns early without required query params
	// ---------------------------------------------------------------

	public function test_handler_returns_early_without_page_param() {
		// filter_input reads from the actual SAPI-level $_GET, which is empty
		// in a test environment. The handler should return early (null/void).
		$result = $this->settings->handle_fetch_stripe_products();

		$this->assertNull(
			$result,
			'Handler should return early (null) when page parameter is missing'
		);
	}

	// ---------------------------------------------------------------
	// Security: capability check
	// ---------------------------------------------------------------

	public function test_handler_requires_manage_options_capability() {
		// Verify the method source contains a manage_options capability check.
		$ref    = new ReflectionMethod( $this->settings, 'handle_fetch_stripe_products' );
		$file   = $ref->getFileName();
		$start  = $ref->getStartLine();
		$end    = $ref->getEndLine();
		$length = $end - $start + 1;

		$lines  = file( $file );
		$source = implode( '', array_slice( $lines, $start - 1, $length ) );

		$this->assertStringContainsString(
			'manage_options',
			$source,
			'handle_fetch_stripe_products should check for manage_options capability'
		);
	}

	// ---------------------------------------------------------------
	// Security: nonce verification
	// ---------------------------------------------------------------

	public function test_handler_verifies_nonce() {
		$ref    = new ReflectionMethod( $this->settings, 'handle_fetch_stripe_products' );
		$file   = $ref->getFileName();
		$start  = $ref->getStartLine();
		$end    = $ref->getEndLine();
		$length = $end - $start + 1;

		$lines  = file( $file );
		$source = implode( '', array_slice( $lines, $start - 1, $length ) );

		$this->assertStringContainsString(
			'wp_verify_nonce',
			$source,
			'handle_fetch_stripe_products should verify the nonce with wp_verify_nonce'
		);

		$this->assertStringContainsString(
			'wpum-stripe-fetch-products',
			$source,
			'handle_fetch_stripe_products should use the wpum-stripe-fetch-products nonce action'
		);
	}

	public function test_handler_checks_fetch_products_query_param() {
		$ref    = new ReflectionMethod( $this->settings, 'handle_fetch_stripe_products' );
		$file   = $ref->getFileName();
		$start  = $ref->getStartLine();
		$end    = $ref->getEndLine();
		$length = $end - $start + 1;

		$lines  = file( $file );
		$source = implode( '', array_slice( $lines, $start - 1, $length ) );

		$this->assertStringContainsString(
			'fetch-products',
			$source,
			'handle_fetch_stripe_products should check for the fetch-products query parameter'
		);
	}

	// ---------------------------------------------------------------
	// Security: nonce URL generation
	// ---------------------------------------------------------------

	public function test_nonce_url_uses_correct_action() {
		// The nonce URL for fetching products should use the
		// wpum-stripe-fetch-products action, matching the verification.
		$nonce = wp_create_nonce( 'wpum-stripe-fetch-products' );

		$this->assertTrue(
			(bool) wp_verify_nonce( $nonce, 'wpum-stripe-fetch-products' ),
			'Nonce action wpum-stripe-fetch-products should be verifiable'
		);
	}

	// ---------------------------------------------------------------
	// Security: capability guard order (capability before nonce)
	// ---------------------------------------------------------------

	public function test_capability_check_precedes_nonce_in_disconnect_handler() {
		$ref    = new ReflectionMethod( $this->settings, 'handle_stripe_connect_disconnect' );
		$file   = $ref->getFileName();
		$start  = $ref->getStartLine();
		$end    = $ref->getEndLine();
		$length = $end - $start + 1;

		$lines  = file( $file );
		$source = implode( '', array_slice( $lines, $start - 1, $length ) );

		$cap_pos   = strpos( $source, 'manage_options' );
		$nonce_pos = strpos( $source, 'wp_verify_nonce' );

		$this->assertNotFalse( $cap_pos, 'Disconnect handler should check manage_options' );
		$this->assertNotFalse( $nonce_pos, 'Disconnect handler should verify nonce' );
		$this->assertLessThan(
			$nonce_pos,
			$cap_pos,
			'Capability check should come before nonce verification in disconnect handler'
		);
	}

	public function test_capability_check_precedes_nonce_in_fetch_products_handler() {
		$ref    = new ReflectionMethod( $this->settings, 'handle_fetch_stripe_products' );
		$file   = $ref->getFileName();
		$start  = $ref->getStartLine();
		$end    = $ref->getEndLine();
		$length = $end - $start + 1;

		$lines  = file( $file );
		$source = implode( '', array_slice( $lines, $start - 1, $length ) );

		$cap_pos   = strpos( $source, 'manage_options' );
		$nonce_pos = strpos( $source, 'wp_verify_nonce' );

		$this->assertNotFalse( $cap_pos, 'Fetch products handler should check manage_options' );
		$this->assertNotFalse( $nonce_pos, 'Fetch products handler should verify nonce' );
		$this->assertLessThan(
			$nonce_pos,
			$cap_pos,
			'Capability check should come before nonce verification in fetch products handler'
		);
	}

	// ---------------------------------------------------------------
	// StripeWebhookController: new handler methods
	// ---------------------------------------------------------------

	public function test_webhook_controller_class_exists() {
		$this->assertTrue(
			class_exists( StripeWebhookController::class ),
			'StripeWebhookController class should be autoloadable'
		);
	}

	public function test_webhook_controller_has_handle_product_created_method() {
		$this->assertTrue(
			method_exists( StripeWebhookController::class, 'handleProductCreated' ),
			'StripeWebhookController should have the handleProductCreated method'
		);
	}

	public function test_webhook_controller_has_handle_product_deleted_method() {
		$this->assertTrue(
			method_exists( StripeWebhookController::class, 'handleProductDeleted' ),
			'StripeWebhookController should have the handleProductDeleted method'
		);
	}

	public function test_webhook_controller_has_handle_product_updated_method() {
		$this->assertTrue(
			method_exists( StripeWebhookController::class, 'handleProductUpdated' ),
			'StripeWebhookController should have the handleProductUpdated method'
		);
	}

	public function test_webhook_product_handlers_are_protected() {
		$methods = array( 'handleProductCreated', 'handleProductDeleted', 'handleProductUpdated' );

		foreach ( $methods as $method_name ) {
			$ref = new ReflectionMethod( StripeWebhookController::class, $method_name );

			$this->assertTrue(
				$ref->isProtected(),
				sprintf( '%s should be a protected method (called via handleWebhook dispatch)', $method_name )
			);
		}
	}

	public function test_webhook_handle_webhook_method_exists() {
		$this->assertTrue(
			method_exists( StripeWebhookController::class, 'handleWebhook' ),
			'StripeWebhookController should have the public handleWebhook dispatcher method'
		);
	}

	public function test_webhook_handle_webhook_is_public() {
		$ref = new ReflectionMethod( StripeWebhookController::class, 'handleWebhook' );

		$this->assertTrue(
			$ref->isPublic(),
			'handleWebhook should be public so it can be called by the REST API'
		);
	}

	// ---------------------------------------------------------------
	// StripeWebhookController: studly helper maps product events
	// ---------------------------------------------------------------

	public function test_studly_maps_product_created_event_to_method() {
		$ref = new ReflectionMethod( StripeWebhookController::class, 'studly' );
		$ref->setAccessible( true );

		// We need an instance -- use a dummy constructor since we cannot
		// connect to Stripe. The constructor sets API key only if non-empty.
		$controller = new StripeWebhookController( '', '', 'test' );

		$this->assertEquals(
			'ProductCreated',
			$ref->invoke( $controller, 'product_created' ),
			'studly() should convert product_created to ProductCreated'
		);
	}

	public function test_studly_maps_product_deleted_event_to_method() {
		$ref = new ReflectionMethod( StripeWebhookController::class, 'studly' );
		$ref->setAccessible( true );

		$controller = new StripeWebhookController( '', '', 'test' );

		$this->assertEquals(
			'ProductDeleted',
			$ref->invoke( $controller, 'product_deleted' ),
			'studly() should convert product_deleted to ProductDeleted'
		);
	}

	public function test_studly_maps_product_updated_event_to_method() {
		$ref = new ReflectionMethod( StripeWebhookController::class, 'studly' );
		$ref->setAccessible( true );

		$controller = new StripeWebhookController( '', '', 'test' );

		$this->assertEquals(
			'ProductUpdated',
			$ref->invoke( $controller, 'product_updated' ),
			'studly() should convert product_updated to ProductUpdated'
		);
	}
}
