<?php
/**
 * Tests for registration form field validation.
 */

namespace WPUM\Tests\Registration;

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

	public function test_duplicate_username_fails() {
		$username = 'dupeuser_' . wp_rand();

		// Create first user.
		$this->factory()->user->create( array( 'user_login' => $username ) );

		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'username' => $username ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with duplicate username should fail' );
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

	public function test_illegal_username_characters_fail() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'username' => 'user name with spaces!' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with illegal username characters should fail' );
	}

	public function test_excluded_username_fails() {
		// Enable the excluded usernames setting.
		wpum_update_option( 'exclude_usernames', true );

		// Get the disabled usernames list and ensure 'admin' is excluded.
		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'username' => 'admin' ),
		) );
		$user_id = $this->submit_registration( $data );

		// Restore setting.
		wpum_update_option( 'exclude_usernames', false );

		$this->assertFalse( $user_id, 'Registration with excluded username should fail' );
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
}
