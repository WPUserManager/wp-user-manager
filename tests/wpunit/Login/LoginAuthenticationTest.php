<?php
/**
 * Tests for successful login authentication.
 */

require_once __DIR__ . '/LoginTestCase.php';

class LoginAuthenticationTest extends LoginTestCase {

	/**
	 * Test that valid credentials authenticate successfully (step increments).
	 */
	public function test_valid_credentials_authenticate() {
		$this->factory()->user->create( array(
			'user_login' => 'valid_login_user',
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'valid_login@example.com',
		) );

		$form   = $this->submit_login( 'valid_login_user', 'StrongP@ss1!' );
		$errors = $this->get_form_errors( $form );
		$step   = $this->get_form_step( $form );

		$this->assertEmpty( $errors, 'Should not have errors on valid login.' );
		$this->assertEquals( 1, $step, 'Step should increment to 1 on successful authentication.' );
	}

	/**
	 * Test authentication with email instead of username.
	 */
	public function test_authentication_with_email() {
		$this->factory()->user->create( array(
			'user_login' => 'email_login_user',
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'email_login@example.com',
		) );

		$form   = $this->submit_login( 'email_login@example.com', 'StrongP@ss1!' );
		$errors = $this->get_form_errors( $form );
		$step   = $this->get_form_step( $form );

		$this->assertEmpty( $errors, 'Should not have errors when logging in with email.' );
		$this->assertEquals( 1, $step, 'Step should increment to 1 on successful email authentication.' );
	}

	/**
	 * Test that after successful authentication, the step advances past submit.
	 */
	public function test_successful_login_advances_step() {
		$this->factory()->user->create( array(
			'user_login' => 'step_check_user',
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'step_check@example.com',
		) );

		$form = $this->submit_login( 'step_check_user', 'StrongP@ss1!' );
		$step = $this->get_form_step( $form );

		// Step 0 = 'submit', step 1 = 'done', so after submit_handler succeeds step should be 1.
		$this->assertGreaterThan( 0, $step, 'Step should advance beyond 0 on successful login.' );
	}

	/**
	 * Test that the login form has the expected field groups.
	 */
	public function test_login_form_has_expected_fields() {
		$form = \WPUM_Form_Login::instance();
		$form->init_fields();

		$fields = $form->get_fields( 'login' );

		$this->assertArrayHasKey( 'username', $fields, 'Should have username field.' );
		$this->assertArrayHasKey( 'password', $fields, 'Should have password field.' );
		$this->assertArrayHasKey( 'remember', $fields, 'Should have remember field.' );
	}
}
