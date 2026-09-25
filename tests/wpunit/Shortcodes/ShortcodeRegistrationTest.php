<?php
/**
 * Tests that all WPUM shortcodes are registered.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ShortcodeRegistrationTest extends WPUMTestCase {

	/**
	 * Test the login form shortcode is registered.
	 */
	public function test_login_form_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_login_form' ), 'wpum_login_form shortcode should be registered.' );
	}

	/**
	 * Test the register shortcode is registered.
	 */
	public function test_register_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_register' ), 'wpum_register shortcode should be registered.' );
	}

	/**
	 * Test the password recovery shortcode is registered.
	 */
	public function test_password_recovery_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_password_recovery' ), 'wpum_password_recovery shortcode should be registered.' );
	}

	/**
	 * Test the account shortcode is registered.
	 */
	public function test_account_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_account' ), 'wpum_account shortcode should be registered.' );
	}

	/**
	 * Test the profile shortcode is registered.
	 */
	public function test_profile_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_profile' ), 'wpum_profile shortcode should be registered.' );
	}

	/**
	 * Test content restriction shortcodes are registered.
	 */
	public function test_content_restriction_shortcodes_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_restrict_logged_in' ), 'wpum_restrict_logged_in shortcode should be registered.' );
		$this->assertTrue( shortcode_exists( 'wpum_restrict_logged_out' ), 'wpum_restrict_logged_out shortcode should be registered.' );
		$this->assertTrue( shortcode_exists( 'wpum_restrict_to_user_roles' ), 'wpum_restrict_to_user_roles shortcode should be registered.' );
		$this->assertTrue( shortcode_exists( 'wpum_restrict_to_users' ), 'wpum_restrict_to_users shortcode should be registered.' );
	}

	/**
	 * Test the directory shortcode is registered.
	 */
	public function test_directory_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_user_directory' ), 'wpum_user_directory shortcode should be registered.' );
	}

	/**
	 * Test the login link shortcode is registered.
	 */
	public function test_login_link_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_login' ), 'wpum_login shortcode should be registered.' );
	}

	/**
	 * Test the logout link shortcode is registered.
	 */
	public function test_logout_link_shortcode_registered() {
		$this->assertTrue( shortcode_exists( 'wpum_logout' ), 'wpum_logout shortcode should be registered.' );
	}
}
