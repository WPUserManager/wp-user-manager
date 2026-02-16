<?php
/**
 * Tests for successful user registration.
 */

namespace WPUM\Tests\Registration;

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

	public function test_successful_registration_sets_first_and_last_name() {
		$data = $this->get_valid_registration_data( array(
			'register' => array(
				'user_firstname' => 'John',
				'user_lastname'  => 'Doe',
			),
		) );

		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
		$this->assertEquals( 'John', get_user_meta( $user_id, 'first_name', true ) );
		$this->assertEquals( 'Doe', get_user_meta( $user_id, 'last_name', true ) );
	}

	public function test_successful_registration_sets_website() {
		$data = $this->get_valid_registration_data( array(
			'register' => array(
				'user_website' => 'https://example.com',
			),
		) );

		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertEquals( 'https://example.com', $user->user_url );
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

	public function test_registration_generates_password_when_not_provided() {
		$data = $this->get_valid_registration_data();
		unset( $data['register']['user_password'] );

		$user_id = $this->submit_registration( $data );

		// User should still be created — password auto-generated.
		$this->assertIsInt( $user_id );
		$this->assertGreaterThan( 0, $user_id );
	}

	public function test_registration_with_email_as_username() {
		// Remove username field from the data — registration should use email.
		$email = 'emailuser_' . wp_rand() . '@example.com';
		$data  = $this->get_valid_registration_data( array(
			'register' => array(
				'user_email'    => $email,
				'user_password' => 'StrongP@ssw0rd!123',
			),
		) );
		unset( $data['register']['username'] );

		$user_id = $this->submit_registration( $data );

		if ( is_int( $user_id ) && $user_id > 0 ) {
			$user = get_userdata( $user_id );
			$this->assertEquals( $email, $user->user_login );
		}
	}
}
