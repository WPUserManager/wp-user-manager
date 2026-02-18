<?php
/**
 * Tests for WPUM email template installation and retrieval.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailTemplatesTest extends WPUMTestCase {

	/**
	 * Test that after wpum_install_emails(), the default emails exist.
	 */
	public function test_default_emails_installed() {
		$emails = wpum_install_emails();

		$this->assertIsArray( $emails, 'Emails should be an array.' );
		$this->assertNotEmpty( $emails, 'Default emails should not be empty.' );
	}

	/**
	 * Test that the registration confirmation email exists.
	 */
	public function test_registration_confirmation_email_exists() {
		$emails = wpum_get_emails();

		$this->assertArrayHasKey( 'registration_confirmation', $emails, 'registration_confirmation email should exist.' );
		$this->assertArrayHasKey( 'subject', $emails['registration_confirmation'], 'Email should have a subject.' );
		$this->assertArrayHasKey( 'content', $emails['registration_confirmation'], 'Email should have content.' );
	}

	/**
	 * Test that the password recovery email exists.
	 */
	public function test_password_recovery_email_exists() {
		$emails = wpum_get_emails();

		$this->assertArrayHasKey( 'password_recovery_request', $emails, 'password_recovery_request email should exist.' );
		$this->assertArrayHasKey( 'subject', $emails['password_recovery_request'], 'Email should have a subject.' );
		$this->assertArrayHasKey( 'content', $emails['password_recovery_request'], 'Email should have content.' );
	}

	/**
	 * Test that the admin notification email exists.
	 */
	public function test_admin_notification_email_exists() {
		$emails = wpum_get_emails();

		$this->assertArrayHasKey( 'registration_admin_notification', $emails, 'registration_admin_notification email should exist.' );
	}

	/**
	 * Test that wpum_get_email returns a specific email by ID.
	 */
	public function test_get_email_returns_specific_email() {
		$email = wpum_get_email( 'registration_confirmation' );

		$this->assertIsArray( $email, 'wpum_get_email should return an array for a valid email ID.' );
		$this->assertArrayHasKey( 'subject', $email );
	}

	/**
	 * Test that wpum_get_email returns false for nonexistent email.
	 */
	public function test_get_email_returns_false_for_nonexistent() {
		$email = wpum_get_email( 'totally_fake_email_id' );

		$this->assertFalse( $email, 'wpum_get_email should return false for nonexistent email ID.' );
	}

	/**
	 * Test that the registered emails list is correct.
	 */
	public function test_registered_emails_list() {
		$registered = wpum_get_registered_emails();

		$this->assertArrayHasKey( 'registration_confirmation', $registered );
		$this->assertArrayHasKey( 'registration_admin_notification', $registered );
		$this->assertArrayHasKey( 'password_recovery_request', $registered );
	}
}
