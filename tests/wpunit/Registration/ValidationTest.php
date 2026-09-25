<?php
/**
 * Tests for registration form field validation.
 *
 * The default form uses email-based registration (no username field).
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class ValidationTest extends RegistrationTestCase {

	public function test_duplicate_email_fails() {
		$email = 'dupe_' . wp_rand() . '@example.com';

		// Create first user.
		$this->factory()->user->create( array( 'user_email' => $email ) );

		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => $email ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with duplicate email should fail' );
	}

	public function test_invalid_email_fails() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => 'not-an-email' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with invalid email should fail' );
	}

	public function test_empty_honeypot_passes() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'robo' => '' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
	}

	public function test_filled_honeypot_fails() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'robo' => 'bot-filled-this' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with filled honeypot should fail' );
	}

	public function test_empty_email_fails() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => '' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with empty email should fail' );
	}

	public function test_empty_password_fails() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'user_password' => '' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with empty password should fail' );
	}

	public function test_weak_password_rejected_when_strength_enforced() {
		// Enable strong password enforcement.
		wpum_update_option( 'disable_strong_passwords', false );

		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'user_password' => '123' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with weak password should fail when strength is enforced' );
	}

	public function test_privacy_not_accepted_fails() {
		$data = $this->get_valid_registration_data( array(
			'register' => array( 'privacy' => '' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration should fail when privacy checkbox is not accepted' );
	}
}
