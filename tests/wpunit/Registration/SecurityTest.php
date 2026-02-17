<?php
/**
 * Tests for registration security: nonce, XSS, sanitisation.
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class SecurityTest extends RegistrationTestCase {

	public function test_missing_nonce_rejects_submission() {
		$data = $this->get_valid_registration_data();

		$fields = isset( $data['register'] ) ? $data['register'] : array();

		$_POST = array_merge(
			array(
				'wpum_form'           => 'registration',
				'registration_nonce'  => '', // Empty nonce.
				'submit_registration' => 'Register',
			),
			$fields
		);

		$form    = \WPUM_Form_Registration::instance();
		$user_id = $form->submit_handler();

		$this->assertFalse( $user_id, 'Submission without nonce should be rejected' );
	}

	public function test_invalid_nonce_rejects_submission() {
		$data = $this->get_valid_registration_data();

		$fields = isset( $data['register'] ) ? $data['register'] : array();

		$_POST = array_merge(
			array(
				'wpum_form'           => 'registration',
				'registration_nonce'  => 'totally-invalid-nonce',
				'submit_registration' => 'Register',
			),
			$fields
		);

		$form    = \WPUM_Form_Registration::instance();
		$user_id = $form->submit_handler();

		$this->assertFalse( $user_id, 'Submission with invalid nonce should be rejected' );
	}

	public function test_missing_submit_button_rejects_submission() {
		$data = $this->get_valid_registration_data();

		$fields = isset( $data['register'] ) ? $data['register'] : array();

		$_POST = array_merge(
			array(
				'wpum_form'          => 'registration',
				'registration_nonce' => wp_create_nonce( 'verify_registration_form' ),
				// Missing 'submit_registration'.
			),
			$fields
		);

		$form    = \WPUM_Form_Registration::instance();
		$user_id = $form->submit_handler();

		$this->assertFalse( $user_id, 'Submission without submit button value should be rejected' );
	}

	public function test_xss_in_first_name_is_sanitised() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array(
				'user_firstname' => '<script>alert("xss")</script>John',
			),
		) );
		$user_id = $this->submit_registration( $data );

		if ( is_int( $user_id ) && $user_id > 0 ) {
			$first_name = get_user_meta( $user_id, 'first_name', true );
			$this->assertStringNotContainsString( '<script>', $first_name, 'Script tags should be stripped from first name' );
		}
	}

	public function test_xss_in_description_is_sanitised() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array(
				'user_description' => '<img src=x onerror=alert(1)>Bio text',
			),
		) );
		$user_id = $this->submit_registration( $data );

		if ( is_int( $user_id ) && $user_id > 0 ) {
			$description = get_user_meta( $user_id, 'description', true );
			$this->assertStringNotContainsString( 'onerror', $description, 'Event handlers should be stripped from description' );
		}
	}

	public function test_sql_injection_in_email_does_not_execute() {
		$data    = $this->get_valid_registration_data( array(
			'register' => array(
				'user_email' => "admin@test.com'; DROP TABLE wp_users; --",
			),
		) );
		$user_id = $this->submit_registration( $data );

		// Should fail validation (invalid email), but the users table should still exist.
		global $wpdb;
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->users}'" );
		$this->assertNotNull( $table_exists, 'Users table should still exist after SQL injection attempt' );
	}

	public function test_special_characters_in_email_handled() {
		$data = $this->get_valid_registration_data( array(
			'register' => array(
				'user_email' => 'user+tag@example.com',
			),
		) );

		$user_id = $this->submit_registration( $data );

		// Plus-addressed emails are valid — should succeed.
		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertEquals( 'user+tag@example.com', $user->user_email );
	}
}
