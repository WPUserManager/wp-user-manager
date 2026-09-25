<?php
/**
 * Tests for password recovery form submission and validation.
 *
 * Note: The submit_handler() uses filter_input(INPUT_POST) for nonce verification,
 * which does not read from the $_POST superglobal in CLI testing.
 * Therefore we test the validation filters directly and verify the submit_handler
 * returns early when nonce/submit button is missing.
 */

require_once __DIR__ . '/PasswordRecoveryTestCase.php';

class PasswordRecoverySubmitTest extends PasswordRecoveryTestCase {

	/**
	 * Test that the validate_username_or_email filter catches a nonexistent email.
	 */
	public function test_nonexistent_email_fails_validation() {
		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => 'nonexistent_' . wp_rand() . '@example.com',
				),
			),
			'password-recovery'
		);

		$this->assertInstanceOf( 'WP_Error', $result, 'Nonexistent email should return a WP_Error.' );
		$this->assertEquals( 'username-validation-error', $result->get_error_code() );
	}

	/**
	 * Test that the validate_username_or_email filter catches a nonexistent username.
	 */
	public function test_nonexistent_username_fails_validation() {
		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => 'nonexistent_username_' . wp_rand(),
				),
			),
			'password-recovery'
		);

		$this->assertInstanceOf( 'WP_Error', $result, 'Nonexistent username should return a WP_Error.' );
		$this->assertEquals( 'username-validation-error', $result->get_error_code() );
	}

	/**
	 * Test that a valid email passes the validate_username_or_email filter.
	 */
	public function test_valid_email_passes_validation() {
		$this->factory()->user->create( array(
			'user_login' => 'recovery_valid_email_user',
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'recovery_valid@example.com',
		) );

		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => 'recovery_valid@example.com',
				),
			),
			'password-recovery'
		);

		$this->assertTrue( $result, 'Valid email should pass validation.' );
	}

	/**
	 * Test that a valid username passes the validate_username_or_email filter.
	 */
	public function test_valid_username_passes_validation() {
		$this->factory()->user->create( array(
			'user_login' => 'recovery_valid_username',
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'recovery_user@example.com',
		) );

		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => 'recovery_valid_username',
				),
			),
			'password-recovery'
		);

		$this->assertTrue( $result, 'Valid username should pass validation.' );
	}

	/**
	 * Test that submit_handler returns early when submit button is missing
	 * (no error, no step increment).
	 */
	public function test_missing_submit_button_returns_early() {
		$_POST = array(
			'username_email' => 'anything@example.com',
		);

		$form = \WPUM_Form_Password_Recovery::instance();
		$form->submit_handler();

		$errors = $this->get_form_errors( $form );
		$step   = $this->get_form_step( $form );

		$this->assertEmpty( $errors, 'Should not have errors when submit button is missing.' );
		$this->assertEquals( 0, $step, 'Step should not increment when submit button is missing.' );
	}

	/**
	 * Test that validate_username_or_email ignores other form names.
	 */
	public function test_validate_username_ignores_other_forms() {
		$form = \WPUM_Form_Password_Recovery::instance();

		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => 'nonexistent@example.com',
				),
			),
			'some-other-form'
		);

		$this->assertTrue( $result, 'Should pass through when form name does not match.' );
	}

	/**
	 * Test that the password recovery form has the correct initial fields.
	 */
	public function test_recovery_form_has_user_field() {
		$form = \WPUM_Form_Password_Recovery::instance();
		$form->init_fields();

		$fields = $form->get_fields( 'user' );

		$this->assertArrayHasKey( 'username_email', $fields, 'Should have username_email field.' );
	}

	/**
	 * Regression test for #188: user whose login looks like an email but differs
	 * from their stored email should still pass validate_username_or_email().
	 */
	public function test_email_as_username_passes_when_differs_from_stored_email() {
		$this->factory()->user->create( array(
			'user_login' => 'john_' . wp_rand() . '@olddomain.com',
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'john_' . wp_rand() . '@newdomain.com',
		) );

		// We need a deterministic login/email pair - recreate with fixed values via a unique suffix.
		$suffix     = wp_rand( 1000, 9999 );
		$user_login = 'user' . $suffix . '@olddomain.com';
		$user_email = 'user' . $suffix . '@newdomain.com';

		$this->factory()->user->create( array(
			'user_login' => $user_login,
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => $user_email,
		) );

		$form = \WPUM_Form_Password_Recovery::instance();

		// Submit the login (which looks like an email) — NOT the stored email.
		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => $user_login,
				),
			),
			'password-recovery'
		);

		$this->assertTrue(
			$result,
			'Validation should pass when the submitted value is a username that looks like an email, even if it differs from the stored email.'
		);
	}

	/**
	 * Regression test for #188: submitting the stored email (not the login) should also pass.
	 */
	public function test_stored_email_passes_when_login_is_different_email_format() {
		$suffix     = wp_rand( 1000, 9999 );
		$user_login = 'loginuser' . $suffix . '@olddomain.com';
		$user_email = 'loginuser' . $suffix . '@newdomain.com';

		$this->factory()->user->create( array(
			'user_login' => $user_login,
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => $user_email,
		) );

		$form = \WPUM_Form_Password_Recovery::instance();

		// Submit the stored email address.
		$result = $form->validate_username_or_email(
			true,
			array(),
			array(
				'user' => array(
					'username_email' => $user_email,
				),
			),
			'password-recovery'
		);

		$this->assertTrue( $result, 'Validation should pass when the submitted value is the stored email address.' );
	}
}
