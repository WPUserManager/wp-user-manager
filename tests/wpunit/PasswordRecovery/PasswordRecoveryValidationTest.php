<?php
/**
 * Tests for password recovery validation methods.
 */

require_once __DIR__ . '/PasswordRecoveryTestCase.php';

class PasswordRecoveryValidationTest extends PasswordRecoveryTestCase {

	/**
	 * Test that mismatched passwords are rejected by validate_passwords().
	 */
	public function test_password_mismatch_rejected() {
		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_passwords(
			true,
			array(),
			array(
				'password' => array(
					'password'   => 'StrongP@ss1!',
					'password_2' => 'DifferentP@ss2!',
				),
			),
			'password-recovery'
		);

		$this->assertInstanceOf( 'WP_Error', $result, 'Mismatched passwords should return a WP_Error.' );
		$this->assertEquals( 'password-validation-nomatch', $result->get_error_code() );
	}

	/**
	 * Test that a weak password is rejected by validate_passwords().
	 */
	public function test_weak_password_rejected() {
		// Ensure strong passwords are not disabled.
		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		unset( $wpum_options['disable_strong_passwords'] );

		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_passwords(
			true,
			array(),
			array(
				'password' => array(
					'password'   => 'weak',
					'password_2' => 'weak',
				),
			),
			'password-recovery'
		);

		$this->assertInstanceOf( 'WP_Error', $result, 'Weak password should return a WP_Error.' );
	}

	/**
	 * Test that valid matching strong passwords pass validation.
	 */
	public function test_valid_passwords_pass_validation() {
		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_passwords(
			true,
			array(),
			array(
				'password' => array(
					'password'   => 'V@lidStr0ng!',
					'password_2' => 'V@lidStr0ng!',
				),
			),
			'password-recovery'
		);

		$this->assertTrue( $result, 'Valid matching strong passwords should pass validation.' );
	}

	/**
	 * Test that validate_passwords ignores non-matching form names.
	 */
	public function test_validate_passwords_ignores_other_forms() {
		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_passwords(
			true,
			array(),
			array(
				'password' => array(
					'password'   => 'Short1!',
					'password_2' => 'Mismatch2!',
				),
			),
			'some-other-form'
		);

		$this->assertTrue( $result, 'Should pass through when form name does not match.' );
	}
}
