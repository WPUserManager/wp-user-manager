<?php
/**
 * Tests for registration email notifications.
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class EmailNotificationTest extends RegistrationTestCase {

	/**
	 * Captured emails.
	 *
	 * @var array
	 */
	protected $sent_emails = array();

	public function _setUp() {
		parent::_setUp();

		// Install default email templates if not present.
		if ( ! get_option( 'wpum_email' ) ) {
			wpum_install_emails();
		}

		// Capture all emails sent during tests.
		$this->sent_emails = array();
		add_filter( 'wp_mail', array( $this, 'capture_email' ) );
	}

	public function _tearDown() {
		remove_filter( 'wp_mail', array( $this, 'capture_email' ) );
		parent::_tearDown();
	}

	/**
	 * Filter callback to capture sent emails.
	 *
	 * @param array $args wp_mail arguments.
	 * @return array
	 */
	public function capture_email( $args ) {
		$this->sent_emails[] = $args;
		return $args;
	}

	public function test_registration_sends_user_confirmation_email() {
		$email = 'emailtest_' . wp_rand() . '@example.com';
		$data  = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => $email ),
		) );

		$user_id = $this->submit_registration( $data );
		$this->assertIsInt( $user_id );

		// Find an email sent to the registered user.
		$user_emails = array_filter( $this->sent_emails, function ( $mail ) use ( $email ) {
			$to = is_array( $mail['to'] ) ? implode( ',', $mail['to'] ) : $mail['to'];
			return strpos( $to, $email ) !== false;
		} );

		$this->assertNotEmpty( $user_emails, 'A confirmation email should be sent to the new user' );
	}

	public function test_registration_sends_admin_notification_email() {
		$admin_email = get_option( 'admin_email' );
		$data        = $this->get_valid_registration_data();

		$user_id = $this->submit_registration( $data );
		$this->assertIsInt( $user_id );

		// Find an email sent to the admin.
		$admin_emails = array_filter( $this->sent_emails, function ( $mail ) use ( $admin_email ) {
			$to = is_array( $mail['to'] ) ? implode( ',', $mail['to'] ) : $mail['to'];
			return strpos( $to, $admin_email ) !== false;
		} );

		$this->assertNotEmpty( $admin_emails, 'An admin notification email should be sent after registration' );
	}

	public function test_user_email_contains_login_credentials() {
		$email = 'tagtest_' . wp_rand() . '@example.com';
		$data  = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => $email ),
		) );

		$user_id = $this->submit_registration( $data );
		$this->assertIsInt( $user_id );

		// Find the user's email and check for their login (email) in the body.
		$user_emails = array_filter( $this->sent_emails, function ( $mail ) use ( $email ) {
			$to = is_array( $mail['to'] ) ? implode( ',', $mail['to'] ) : $mail['to'];
			return strpos( $to, $email ) !== false;
		} );

		if ( ! empty( $user_emails ) ) {
			$first_email = reset( $user_emails );
			$this->assertStringContainsString(
				$email,
				$first_email['message'],
				'User confirmation email should contain the login (email address)'
			);
		}
	}
}
