<?php
/**
 * Tests for WPUM helper functions.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class HelperFunctionsTest extends WPUMTestCase {

	/**
	 * Test that wpum_get_option returns a stored value.
	 */
	public function test_wpum_get_option_returns_value() {
		wpum_update_option( 'test_option_key', 'test_value' );

		$result = wpum_get_option( 'test_option_key' );

		$this->assertEquals( 'test_value', $result );
	}

	/**
	 * Test that wpum_get_option returns default when key is not set.
	 */
	public function test_wpum_get_option_returns_default() {
		$result = wpum_get_option( 'nonexistent_key_' . wp_rand(), 'my_default' );

		$this->assertEquals( 'my_default', $result );
	}

	/**
	 * Test that wpum_get_core_page_id returns a page ID when set.
	 *
	 * Uses the wpum_get_option_login_page filter to inject the value because
	 * the $wpum_options global may be initialised as a non-array (false) by
	 * wpum_get_settings() during test bootstrap, and reassigning it inside the
	 * test method can create a scope-local copy that wpum_get_option() does not
	 * see when it declares its own `global $wpum_options`.
	 */
	public function test_wpum_get_core_page_id_returns_page_id() {
		// Create a page and set it as the login page.
		$page_id = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_title'  => 'Login Page',
			'post_status' => 'publish',
		) );

		// Use the per-key filter that wpum_get_option applies.
		// wpum_get_core_page_id expects the option to be an array: [ page_id ].
		$filter = function() use ( $page_id ) {
			return array( $page_id );
		};
		add_filter( 'wpum_get_option_login_page', $filter );

		$result = wpum_get_core_page_id( 'login' );

		remove_filter( 'wpum_get_option_login_page', $filter );

		$this->assertEquals( $page_id, $result );
	}

	/**
	 * Test that wpum_get_roles returns an array of roles.
	 */
	public function test_wpum_get_roles_returns_roles() {
		// Clear transient first.
		delete_transient( 'wpum_get_roles' );

		$roles = wpum_get_roles( true );

		$this->assertIsArray( $roles, 'wpum_get_roles should return an array.' );
		$this->assertNotEmpty( $roles, 'Roles array should not be empty.' );
	}

	/**
	 * Test that wpum_get_login_methods returns the expected methods.
	 */
	public function test_wpum_get_login_methods_returns_methods() {
		$methods = wpum_get_login_methods();

		$this->assertIsArray( $methods );
		$this->assertArrayHasKey( 'username', $methods );
		$this->assertArrayHasKey( 'email', $methods );
		$this->assertArrayHasKey( 'username_email', $methods );
	}

	/**
	 * Test that wpum_get_option applies filters.
	 */
	public function test_wpum_get_option_applies_filters() {
		add_filter( 'wpum_get_option_filtered_test_key', function( $value ) {
			return 'filtered_value';
		} );

		$result = wpum_get_option( 'filtered_test_key', 'default' );

		$this->assertEquals( 'filtered_value', $result );

		remove_all_filters( 'wpum_get_option_filtered_test_key' );
	}

	/**
	 * Test that wpum_get_core_page_id returns a falsy value for unknown page types.
	 */
	public function test_wpum_get_core_page_id_returns_falsy_for_unknown() {
		$result = wpum_get_core_page_id( 'totally_fake_page_type' );

		$this->assertEmpty( $result, 'Should return a falsy value for unknown page types.' );
	}

	/**
	 * Test that wpum_is_registration_enabled returns truthy when registrations are enabled.
	 */
	public function test_wpum_is_registration_enabled() {
		update_option( 'users_can_register', true );
		$result = wpum_is_registration_enabled();
		$this->assertNotEmpty( $result, 'Should return truthy when registrations are enabled.' );
	}

	/**
	 * Test that wpum_get_core_page_id returns null when called with null.
	 */
	public function test_wpum_get_core_page_id_returns_null_for_null_input() {
		$result = wpum_get_core_page_id( null );

		$this->assertNull( $result );
	}

	/**
	 * Test that wpum_get_login_methods returns an array with 3 items.
	 */
	public function test_wpum_get_login_methods_returns_three_methods() {
		$methods = wpum_get_login_methods();

		$this->assertCount( 3, $methods, 'Should have exactly 3 login methods.' );
	}
}
