<?php
/**
 * Tests for the default subject fallback when a stored email has no subject.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/115
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailSubjectFallbackTest extends WPUMTestCase {

	/**
	 * Captured emails.
	 *
	 * @var array
	 */
	protected $sent_emails = array();

	public function _setUp() {
		parent::_setUp();

		$this->sent_emails = array();
		add_filter( 'wp_mail', array( $this, 'capture_email' ) );
	}

	public function _tearDown() {
		remove_filter( 'wp_mail', array( $this, 'capture_email' ) );
		delete_option( 'wpum_email' );

		parent::_tearDown();
	}

	/**
	 * Filter callback to capture sent emails.
	 *
	 * @param array $args wp_mail arguments.
	 *
	 * @return array
	 */
	public function capture_email( $args ) {
		$this->sent_emails[] = $args;

		return $args;
	}

	/**
	 * Store an email as the customizer would when only some fields were saved.
	 *
	 * @param array $email Partial email data.
	 *
	 * @return void
	 */
	protected function store_partial_registration_email( $email ) {
		update_option( 'wpum_email', array(
			'registration_confirmation' => $email,
		) );
	}

	/**
	 * Issue #115: the subject key was never written to the database.
	 */
	public function test_issue_115_missing_subject_falls_back_to_default() {
		$this->store_partial_registration_email( array(
			'title'   => 'Custom heading',
			'content' => '<p>Custom content</p>',
		) );

		$email = wpum_get_email( 'registration_confirmation' );

		$defaults = wpum_get_default_emails();

		$this->assertArrayHasKey( 'subject', $email, 'A missing subject should be filled in.' );
		$this->assertSame( $defaults['registration_confirmation']['subject'], $email['subject'] );
	}

	/**
	 * Issue #115: the subject was saved blank.
	 */
	public function test_issue_115_empty_subject_falls_back_to_default() {
		$this->store_partial_registration_email( array(
			'title'   => 'Custom heading',
			'subject' => '   ',
			'content' => '<p>Custom content</p>',
		) );

		$email = wpum_get_email( 'registration_confirmation' );

		$defaults = wpum_get_default_emails();

		$this->assertSame( $defaults['registration_confirmation']['subject'], $email['subject'] );
	}

	/**
	 * A saved subject must still win over the default.
	 */
	public function test_saved_subject_is_not_overwritten() {
		$this->store_partial_registration_email( array(
			'title'   => 'Custom heading',
			'subject' => 'My own subject',
			'content' => '<p>Custom content</p>',
		) );

		$email = wpum_get_email( 'registration_confirmation' );

		$this->assertSame( 'My own subject', $email['subject'] );
		$this->assertSame( '<p>Custom content</p>', $email['content'], 'Saved content must be preserved.' );
		$this->assertSame( 'Custom heading', $email['title'], 'Saved title must be preserved.' );
	}

	/**
	 * An email registered by an addon has no defaults, so it falls back to the heading.
	 */
	public function test_unknown_email_falls_back_to_title() {
		update_option( 'wpum_email', array(
			'addon_email' => array(
				'title'   => 'Addon heading',
				'content' => '<p>Addon content</p>',
			),
		) );

		$email = wpum_get_email( 'addon_email' );

		$this->assertSame( 'Addon heading', $email['subject'] );
	}

	/**
	 * The registration confirmation email still sends, with a subject, when the
	 * stored email has no subject key.
	 */
	public function test_issue_115_registration_email_sends_with_subject() {
		$this->store_partial_registration_email( array(
			'title'   => 'Custom heading',
			'content' => '<p>Hello {username}</p>',
		) );

		$user_id = $this->factory()->user->create( array(
			'user_email' => 'subjectfallback_' . wp_rand() . '@example.com',
		) );

		wpum_send_registration_confirmation_email( $user_id );

		$user = get_user_by( 'id', $user_id );

		$user_emails = array_filter( $this->sent_emails, function ( $mail ) use ( $user ) {
			$to = is_array( $mail['to'] ) ? implode( ',', $mail['to'] ) : $mail['to'];

			return false !== strpos( $to, $user->user_email );
		} );

		$this->assertNotEmpty( $user_emails, 'The confirmation email should still be sent.' );

		$first = reset( $user_emails );

		$this->assertNotEmpty( trim( $first['subject'] ), 'The email should have a subject.' );
	}

	/**
	 * Installing the emails repairs an email that was stored without a subject.
	 */
	public function test_install_emails_repairs_partial_stored_email() {
		$this->store_partial_registration_email( array(
			'content' => '<p>Custom content</p>',
		) );

		$emails = wpum_install_emails();

		$defaults = wpum_get_default_emails();

		$this->assertSame( $defaults['registration_confirmation']['subject'], $emails['registration_confirmation']['subject'] );
		$this->assertSame( '<p>Custom content</p>', $emails['registration_confirmation']['content'] );
		$this->assertArrayHasKey( 'password_recovery_request', $emails, 'The other default emails should still be installed.' );
	}
}
