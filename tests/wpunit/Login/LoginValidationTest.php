<?php
/**
 * Tests for login form validation.
 */

require_once __DIR__ . '/LoginTestCase.php';

class LoginValidationTest extends LoginTestCase {

	/**
	 * Test that submitting with an empty username produces an error.
	 */
	public function test_empty_username_rejected() {
		$form   = $this->submit_login( '', 'SomeP@ssw0rd!' );
		$errors = $this->get_form_errors( $form );

		$this->assertNotEmpty( $errors, 'Expected a validation error for empty username.' );
	}

	/**
	 * Test that submitting with an empty password produces an error.
	 */
	public function test_empty_password_rejected() {
		$form   = $this->submit_login( 'someuser', '' );
		$errors = $this->get_form_errors( $form );

		$this->assertNotEmpty( $errors, 'Expected a validation error for empty password.' );
	}

	/**
	 * Test that a wrong password is rejected.
	 */
	public function test_wrong_password_rejected() {
		$this->factory()->user->create( array(
			'user_login' => 'logintest_user',
			'user_pass'  => 'CorrectP@ss1!',
			'user_email' => 'logintest@example.com',
		) );

		$form   = $this->submit_login( 'logintest_user', 'WrongP@ss999!' );
		$errors = $this->get_form_errors( $form );

		$this->assertNotEmpty( $errors, 'Expected an error for wrong password.' );
	}

	/**
	 * Test that a nonexistent user is rejected.
	 */
	public function test_nonexistent_user_rejected() {
		$form   = $this->submit_login( 'absolutely_nonexistent_user_' . wp_rand(), 'SomeP@ssw0rd!' );
		$errors = $this->get_form_errors( $form );

		$this->assertNotEmpty( $errors, 'Expected an error for nonexistent user.' );
	}

	/**
	 * Test that missing submit button key causes early return (no errors, no step increment).
	 */
	public function test_missing_submit_button_returns_early() {
		$form = $this->submit_login( 'someuser', 'SomeP@ssw0rd!', false, false );

		$errors = $this->get_form_errors( $form );
		$step   = $this->get_form_step( $form );

		$this->assertEmpty( $errors, 'Should not have errors when submit button is missing.' );
		$this->assertEquals( 0, $step, 'Step should not increment when submit button is missing.' );
	}
}
