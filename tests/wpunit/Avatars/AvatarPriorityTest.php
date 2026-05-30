<?php
/**
 * Tests for the WPUM_Avatars filter priority fix (#411).
 *
 * When both a custom avatar and a site-wide default avatar are configured,
 * the custom avatar filter (set_avatar_url) must run AFTER the default avatar
 * filter (set_default_avatar) so the custom avatar takes precedence.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/pull/411
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class AvatarPriorityTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		// Enable the custom_avatars and default_avatar WPUM options so both
		// code paths in the WPUM_Avatars constructor are entered.
		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		$wpum_options['custom_avatars']  = true;
		$wpum_options['default_avatar']  = 'http://example.com/default-avatar.jpg';

		// Instantiate a fresh WPUM_Avatars so both filters are registered.
		new WPUM_Avatars();
	}

	public function _tearDown() {
		// Remove filters that our fresh instance added, so they don't leak
		// into other tests.
		remove_all_filters( 'get_avatar_url' );

		global $wpum_options;
		if ( is_array( $wpum_options ) ) {
			unset( $wpum_options['custom_avatars'], $wpum_options['default_avatar'] );
		}

		parent::_tearDown();
	}

	/**
	 * Verify the WPUM_Avatars class exists and is loadable.
	 */
	public function test_wpum_avatars_class_exists() {
		$this->assertTrue( class_exists( 'WPUM_Avatars' ), 'WPUM_Avatars class should be loaded.' );
	}

	/**
	 * The set_avatar_url callback (custom avatar) must be registered at priority 11.
	 */
	public function test_set_avatar_url_registered_at_priority_11() {
		$priority = has_filter( 'get_avatar_url', array( $this->get_avatars_instance(), 'set_avatar_url' ) );

		$this->assertNotFalse( $priority, 'set_avatar_url should be registered on get_avatar_url.' );
		$this->assertSame( 11, $priority, 'set_avatar_url must be registered at priority 11.' );
	}

	/**
	 * The set_default_avatar callback must be registered at priority 10 (the default).
	 */
	public function test_set_default_avatar_registered_at_priority_10() {
		$priority = has_filter( 'get_avatar_url', array( $this->get_avatars_instance(), 'set_default_avatar' ) );

		$this->assertNotFalse( $priority, 'set_default_avatar should be registered on get_avatar_url.' );
		$this->assertSame( 10, $priority, 'set_default_avatar must be registered at priority 10.' );
	}

	/**
	 * The custom avatar filter priority must be strictly greater than the
	 * default avatar filter priority, so the custom avatar wins.
	 */
	public function test_custom_avatar_priority_is_higher_than_default() {
		$instance = $this->get_avatars_instance();

		$custom_priority  = has_filter( 'get_avatar_url', array( $instance, 'set_avatar_url' ) );
		$default_priority = has_filter( 'get_avatar_url', array( $instance, 'set_default_avatar' ) );

		$this->assertNotFalse( $custom_priority, 'set_avatar_url should be registered.' );
		$this->assertNotFalse( $default_priority, 'set_default_avatar should be registered.' );

		$this->assertGreaterThan(
			$default_priority,
			$custom_priority,
			'Custom avatar filter (set_avatar_url) must run after the default avatar filter (set_default_avatar).'
		);
	}

	/**
	 * Helper: return the WPUM_Avatars instance that was created in _setUp.
	 *
	 * We retrieve it from the filter registry rather than storing a reference,
	 * so the test truly reflects what WordPress sees.
	 *
	 * @return WPUM_Avatars
	 */
	private function get_avatars_instance() {
		global $wp_filter;

		if ( ! isset( $wp_filter['get_avatar_url'] ) ) {
			$this->fail( 'get_avatar_url filter is not registered.' );
		}

		foreach ( $wp_filter['get_avatar_url']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if (
					is_array( $callback['function'] ) &&
					$callback['function'][0] instanceof WPUM_Avatars
				) {
					return $callback['function'][0];
				}
			}
		}

		$this->fail( 'Could not find a WPUM_Avatars instance in the get_avatar_url filter.' );
	}
}
