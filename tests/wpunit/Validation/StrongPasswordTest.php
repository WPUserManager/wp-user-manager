<?php
/**
 * Tests for the validate_strong_password() method in WPUM_Form.
 *
 * Since validate_strong_password is protected, we test it through a concrete form class.
 * We use WPUM_Form_Password_Recovery's validate_passwords() which calls validate_strong_password().
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class StrongPasswordTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUM_Form_Password_Recovery' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-password-recovery.php';
		}

		// Ensure strong passwords are not disabled.
		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		unset( $wpum_options['disable_strong_passwords'] );
	}

	public function _tearDown() {
		$this->reset_singleton( 'WPUM_Form_Password_Recovery', 'instance' );

		remove_all_filters( 'submit_wpum_form_validate_fields' );
		remove_all_filters( 'wpum_strong_password_min_length' );
		remove_all_filters( 'wpum_strong_password_is_valid' );
		remove_all_actions( 'wp' );

		parent::_tearDown();
	}

	/**
	 * Helper to test a password through validate_passwords().
	 *
	 * @param string $password
	 *
	 * @return bool|WP_Error
	 */
	private function validate_password( $password ) {
		$form = \WPUM_Form_Password_Recovery::instance();

		return $form->validate_passwords(
			true,
			array(),
			array(
				'password' => array(
					'password'   => $password,
					'password_2' => $password,
				),
			),
			'password-recovery'
		);
	}

	/**
	 * Test that a password shorter than 8 characters is rejected.
	 */
	public function test_password_too_short_rejected() {
		$result = $this->validate_password( 'Sh0r!' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Password shorter than 8 chars should be rejected.' );
	}

	/**
	 * Test that a password without uppercase is rejected.
	 */
	public function test_password_no_uppercase_rejected() {
		$result = $this->validate_password( 'lowercase1!pass' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Password without uppercase should be rejected.' );
	}

	/**
	 * Test that a password without a digit is rejected.
	 */
	public function test_password_no_digit_rejected() {
		$result = $this->validate_password( 'NoDigitPass!' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Password without digit should be rejected.' );
	}

	/**
	 * Test that a password without a special character is rejected.
	 */
	public function test_password_no_special_char_rejected() {
		$result = $this->validate_password( 'NoSpecial1Pass' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Password without special character should be rejected.' );
	}

	/**
	 * Test that a valid strong password passes.
	 */
	public function test_valid_strong_password_accepted() {
		$result = $this->validate_password( 'V@lidStr0ng!Pass' );

		$this->assertTrue( $result, 'Valid strong password should pass validation.' );
	}

	/**
	 * Test that the min length filter changes the requirement.
	 */
	public function test_min_length_filter_works() {
		add_filter( 'wpum_strong_password_min_length', function() {
			return 20;
		} );

		// This password is strong but only 16 chars.
		$result = $this->validate_password( 'V@lidStr0ng!Pass' );

		$this->assertInstanceOf( 'WP_Error', $result, 'Password should fail when min length filter increases requirement.' );

		remove_all_filters( 'wpum_strong_password_min_length' );
	}

	/**
	 * Test that the bypass filter can override validation.
	 */
	public function test_bypass_filter_works() {
		add_filter( 'wpum_strong_password_is_valid', function() {
			return true;
		} );

		// This weak password should now pass.
		$result = $this->validate_password( 'weak' );

		$this->assertTrue( $result, 'Password should pass when bypass filter returns true.' );

		remove_all_filters( 'wpum_strong_password_is_valid' );
	}
}
