<?php
/**
 * Tests for profile tab validation — ensures tab query var is validated
 * against registered tabs to prevent path traversal / LFI.
 */

class TabValidationTest extends \Codeception\TestCase\WPTestCase {

	public function _setUp() {
		parent::_setUp();

		if ( ! function_exists( 'wpum_get_active_profile_tab' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/functions.php';
		}
	}

	public function _tearDown() {
		set_query_var( 'tab', '' );
		parent::_tearDown();
	}

	public function test_valid_tab_is_returned() {
		set_query_var( 'tab', 'about' );
		$this->assertEquals( 'about', wpum_get_active_profile_tab() );
	}

	public function test_valid_posts_tab_is_returned() {
		// Enable posts tab.
		wpum_update_option( 'profile_posts', true );
		set_query_var( 'tab', 'posts' );
		$this->assertEquals( 'posts', wpum_get_active_profile_tab() );
	}

	public function test_default_tab_when_no_query_var() {
		set_query_var( 'tab', '' );
		$tab = wpum_get_active_profile_tab();
		$this->assertEquals( 'about', $tab );
	}

	public function test_traversal_tab_returns_default() {
		set_query_var( 'tab', '../../wp-config' );
		$tab = wpum_get_active_profile_tab();
		$this->assertEquals( 'about', $tab );
	}

	public function test_dot_dot_slash_tab_returns_default() {
		set_query_var( 'tab', '../../../etc/passwd' );
		$tab = wpum_get_active_profile_tab();
		$this->assertEquals( 'about', $tab );
	}

	public function test_unregistered_tab_returns_default() {
		set_query_var( 'tab', 'nonexistent_tab_xyz' );
		$tab = wpum_get_active_profile_tab();
		$this->assertEquals( 'about', $tab );
	}

	public function test_null_byte_tab_returns_default() {
		set_query_var( 'tab', "about\0../../wp-config" );
		$tab = wpum_get_active_profile_tab();
		$this->assertEquals( 'about', $tab );
	}

	public function test_backslash_tab_returns_default() {
		set_query_var( 'tab', '..\\..\\wp-config' );
		$tab = wpum_get_active_profile_tab();
		$this->assertEquals( 'about', $tab );
	}
}
