<?php
/**
 * Tests for successful user registration.
 *
 * The default registration form has: user_email, user_password, robo (honeypot), privacy.
 * It uses email-based registration (no username field).
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class SuccessfulRegistrationTest extends RegistrationTestCase {

	public function test_successful_registration_creates_user() {
		$email = 'success_' . wp_rand() . '@example.com';
		$data  = $this->get_valid_registration_data( array(
			'register' => array(
				'user_email'    => $email,
				'user_password' => 'StrongP@ssw0rd!123',
			),
		) );

		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
		$this->assertGreaterThan( 0, $user_id );

		$user = get_userdata( $user_id );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertEquals( $email, $user->user_email );
	}

	public function test_successful_registration_assigns_default_role() {
		$default_role = get_option( 'default_role' );
		$data         = $this->get_valid_registration_data();

		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertTrue( in_array( $default_role, $user->roles, true ) );
	}

	public function test_successful_registration_uses_email_as_login() {
		$email = 'logintest_' . wp_rand() . '@example.com';
		$data  = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => $email ),
		) );

		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertEquals( $email, $user->user_login, 'Email-based registration should use email as user_login' );
	}

	public function test_successful_registration_fires_before_hook() {
		$fired = false;

		add_action( 'wpum_before_registration_start', function () use ( &$fired ) {
			$fired = true;
		} );

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
		$this->assertTrue( $fired, 'wpum_before_registration_start should fire during registration' );
	}

	public function test_successful_registration_fires_after_hook() {
		$captured_user_id = null;

		add_action( 'wpum_after_registration', function ( $user_id ) use ( &$captured_user_id ) {
			$captured_user_id = $user_id;
		} );

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
		$this->assertEquals( $user_id, $captured_user_id, 'wpum_after_registration should fire with the new user ID' );
	}

	public function test_registration_stores_password_correctly() {
		$password = 'MyStr0ng!P@ss';
		$data     = $this->get_valid_registration_data( array(
			'register' => array( 'user_password' => $password ),
		) );

		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		// Verify the password was stored correctly (can authenticate with it).
		$user = wp_authenticate( get_userdata( $user_id )->user_login, $password );
		$this->assertInstanceOf( \WP_User::class, $user );
		$this->assertEquals( $user_id, $user->ID );
	}

	public function test_two_registrations_create_distinct_users() {
		$data1   = $this->get_valid_registration_data();
		$user_id1 = $this->submit_registration( $data1 );

		$data2   = $this->get_valid_registration_data();
		$user_id2 = $this->submit_registration( $data2 );

		$this->assertIsInt( $user_id1 );
		$this->assertIsInt( $user_id2 );
		$this->assertNotEquals( $user_id1, $user_id2 );
	}
}
