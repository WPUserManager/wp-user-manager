<?php
/**
 * Tests for the password change form.
 */

require_once __DIR__ . '/PasswordChangeTestCase.php';

class PasswordChangeTest extends PasswordChangeTestCase {

	/**
	 * Test that a logged-out user cannot use the password change form.
	 * The constructor returns early when the user is not logged in, so steps are not set.
	 */
	public function test_logged_out_user_cannot_change_password() {
		wp_set_current_user( 0 );

		$form  = \WPUM_Form_Password::instance();
		$steps = $form->get_steps();

		$this->assertEmpty( $steps, 'Steps should be empty when user is not logged in.' );
	}

	/**
	 * Test that missing nonce causes early return.
	 */
	public function test_missing_nonce_returns_early() {
		$this->create_and_login_user();

		$_POST = array(
			'password'        => 'NewStr0ng!Pass',
			'password_repeat' => 'NewStr0ng!Pass',
			'submit_password' => 'Change Password',
		);

		$form = \WPUM_Form_Password::instance();
		$form->submit_handler();

		$errors = $this->get_form_errors( $form );
		$step   = $this->get_form_step( $form );

		$this->assertEmpty( $errors, 'Should not have errors when nonce is missing.' );
		$this->assertEquals( 0, $step, 'Step should not increment when nonce is missing.' );
	}

	/**
	 * Test that password validation catches mismatched passwords.
	 */
	public function test_password_mismatch_rejected() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Password::instance();

		$result = $form->validate_password(
			true,
			array(),
			array(
				'password' => array(
					'current_password' => '',
					'password'         => 'StrongP@ss1!',
					'password_repeat'  => 'DifferentP@ss2!',
				),
			),
			'password'
		);

		$this->assertInstanceOf( 'WP_Error', $result, 'Mismatched passwords should return a WP_Error.' );
		$this->assertEquals( 'password-validation-nomatch', $result->get_error_code() );
	}

	/**
	 * Test that weak passwords are rejected.
	 */
	public function test_weak_password_rejected() {
		$this->create_and_login_user();

		// Ensure strong passwords are not disabled.
		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		unset( $wpum_options['disable_strong_passwords'] );

		$form = \WPUM_Form_Password::instance();

		$result = $form->validate_password(
			true,
			array(),
			array(
				'password' => array(
					'current_password' => '',
					'password'         => 'weak',
					'password_repeat'  => 'weak',
				),
			),
			'password'
		);

		$this->assertInstanceOf( 'WP_Error', $result, 'Weak password should return a WP_Error.' );
	}

	/**
	 * Test that a valid password change fires the hook and updates the password.
	 * The submit_handler uses filter_input(INPUT_POST) for nonce which doesn't read
	 * from $_POST in testing. So we test the validate_password method directly
	 * for the success path.
	 */
	public function test_valid_password_passes_validation() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Password::instance();

		$result = $form->validate_password(
			true,
			array(),
			array(
				'password' => array(
					'current_password' => '',
					'password'         => 'NewStr0ng!Pass1',
					'password_repeat'  => 'NewStr0ng!Pass1',
				),
			),
			'password'
		);

		$this->assertTrue( $result, 'Valid matching strong passwords should pass validation.' );
	}

	/**
	 * Test that when current_password option is enabled, wrong current password is rejected.
	 */
	public function test_current_password_validation() {
		$user_id = $this->create_and_login_user();

		// Enable the current_password option.
		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		$wpum_options['current_password'] = true;

		$form = \WPUM_Form_Password::instance();

		$result = $form->validate_password(
			true,
			array(),
			array(
				'password' => array(
					'current_password' => 'WrongCurrent!',
					'password'         => 'NewStr0ng!Pass1',
					'password_repeat'  => 'NewStr0ng!Pass1',
				),
			),
			'password'
		);

		// Reset option.
		unset( $wpum_options['current_password'] );

		$this->assertInstanceOf( 'WP_Error', $result, 'Wrong current password should return a WP_Error.' );
		$this->assertEquals( 'password-validation-wrongcurrent', $result->get_error_code() );
	}

	/**
	 * Test that the password change form has the correct fields when logged in.
	 */
	public function test_password_change_form_has_fields() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Password::instance();
		$form->init_fields();

		$fields = $form->get_fields( 'password' );

		$this->assertArrayHasKey( 'password', $fields, 'Should have password field.' );
		$this->assertArrayHasKey( 'password_repeat', $fields, 'Should have password_repeat field.' );
	}
}
