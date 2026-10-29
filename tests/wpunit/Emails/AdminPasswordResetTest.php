<?php
/**
 * Admins can send WPUM's password recovery email from the users list.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/370
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class AdminPasswordResetTest extends WPUMTestCase {

	/**
	 * @var array
	 */
	protected $sent_emails = array();

	/**
	 * @var mixed
	 */
	private $original_password_page;

	public function _setUp() {
		parent::_setUp();

		$this->sent_emails            = array();
		$this->original_password_page = wpum_get_option( 'password_recovery_page' );

		$page_id = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );
		wpum_update_option( 'password_recovery_page', array( $page_id ) );

		add_filter( 'wp_mail', array( $this, 'capture_email' ) );
	}

	public function _tearDown() {
		remove_filter( 'wp_mail', array( $this, 'capture_email' ) );
		wpum_update_option( 'password_recovery_page', $this->original_password_page );

		parent::_tearDown();
	}

	public function capture_email( $args ) {
		$this->sent_emails[] = $args;

		return $args;
	}

	public function test_sends_the_wpum_recovery_email_with_a_working_key() {
		$user = $this->factory()->user->create_and_get( array( 'user_email' => 'reset-me@example.com' ) );

		$result = wpum_send_password_recovery_email( $user );

		$this->assertTrue( $result );
		$this->assertCount( 1, $this->sent_emails );
		$this->assertSame( 'reset-me@example.com', $this->sent_emails[0]['to'] );

		$this->assertSame( 1, preg_match( '/key=([A-Za-z0-9]+)/',$this->sent_emails[0]['message'], $matches ), 'The email should contain a reset link.' );
		$this->assertStringContainsString( 'action=wpum-reset', $this->sent_emails[0]['message'] );
		$this->assertInstanceOf( 'WP_User', check_password_reset_key( rawurldecode( $matches[1] ), $user->user_login ) );
	}

	public function test_rejects_a_missing_user() {
		$result = wpum_send_password_recovery_email( false );

		$this->assertWPError( $result );
		$this->assertCount( 0, $this->sent_emails );
	}

	public function test_row_action_is_added_for_another_user() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$other = $this->factory()->user->create_and_get();

		$actions = wpum_admin_user_password_reset_link( array(), $other );

		$this->assertArrayHasKey( 'wpum_password_reset', $actions );
		$this->assertStringContainsString( 'action=wpum_send_password_reset', $actions['wpum_password_reset'] );
		$this->assertStringContainsString( 'user=' . $other->ID, $actions['wpum_password_reset'] );
		$this->assertStringContainsString( '_wpnonce=', $actions['wpum_password_reset'] );
	}

	public function test_row_action_is_not_added_for_yourself() {
		$admin = $this->factory()->user->create_and_get( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin->ID );

		$this->assertArrayNotHasKey( 'wpum_password_reset', wpum_admin_user_password_reset_link( array(), $admin ) );
	}

	public function test_row_action_needs_permission_to_edit_the_user() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );
		$other = $this->factory()->user->create_and_get();

		$this->assertArrayNotHasKey( 'wpum_password_reset', wpum_admin_user_password_reset_link( array(), $other ) );
	}

	public function test_row_action_needs_a_password_recovery_page() {
		wpum_update_option( 'password_recovery_page', '' );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$other = $this->factory()->user->create_and_get();

		$this->assertArrayNotHasKey( 'wpum_password_reset', wpum_admin_user_password_reset_link( array(), $other ) );
	}
}
