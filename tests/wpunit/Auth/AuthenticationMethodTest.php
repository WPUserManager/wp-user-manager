<?php
/**
 * Tests for the wpum_authentication() filter in filters.php.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class AuthenticationMethodTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $test_user_id;

	/**
	 * @var string
	 */
	protected $test_username;

	/**
	 * @var string
	 */
	protected $test_email;

	/**
	 * @var string
	 */
	protected $test_password = 'StrongP@ss1!';

	public function _setUp() {
		parent::_setUp();

		$this->test_username = 'auth_method_user_' . wp_rand();
		$this->test_email    = 'auth_method_' . wp_rand() . '@example.com';

		$this->test_user_id = $this->factory()->user->create( array(
			'user_login' => $this->test_username,
			'user_pass'  => $this->test_password,
			'user_email' => $this->test_email,
			'role'       => 'subscriber',
		) );
	}

	public function _tearDown() {
		// Reset login method to default.
		global $wpum_options;
		if ( is_array( $wpum_options ) ) {
			unset( $wpum_options['login_method'] );
		}

		parent::_tearDown();
	}

	/**
	 * Helper to set the login method option safely.
	 *
	 * @param string $method
	 */
	private function set_login_method( $method ) {
		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		$wpum_options['login_method'] = $method;
	}

	/**
	 * Test that username-only mode rejects email login.
	 */
	public function test_username_only_mode_rejects_email_login() {
		$this->set_login_method( 'username' );

		// With username mode, passing an email as 'username' will try get_user_by('login', email) which fails.
		$result = wpum_authentication( null, $this->test_email, $this->test_password );

		// When login method is 'username' and we pass an email, get_user_by('login', email) returns false.
		// The function returns the original null value (no match).
		$this->assertNull( $result, 'Username-only mode should not authenticate via email.' );
	}

	/**
	 * Test that email-only mode rejects username login.
	 */
	public function test_email_only_mode_rejects_username_login() {
		$this->set_login_method( 'email' );

		$result = wpum_authentication( null, $this->test_username, $this->test_password );

		// Username is not an email, so is_email() returns false, triggering the error.
		$this->assertInstanceOf( 'WP_Error', $result, 'Email-only mode should reject non-email username.' );
		$this->assertEquals( 'email_only', $result->get_error_code() );
	}

	/**
	 * Test that email-only mode accepts email login.
	 */
	public function test_email_only_mode_accepts_email_login() {
		$this->set_login_method( 'email' );

		$result = wpum_authentication( null, $this->test_email, $this->test_password );

		$this->assertInstanceOf( 'WP_User', $result, 'Email-only mode should authenticate with valid email.' );
		$this->assertEquals( $this->test_user_id, $result->ID );
	}

	/**
	 * Test that username-only mode accepts username login.
	 */
	public function test_username_only_mode_accepts_username_login() {
		$this->set_login_method( 'username' );

		$result = wpum_authentication( null, $this->test_username, $this->test_password );

		$this->assertInstanceOf( 'WP_User', $result, 'Username-only mode should authenticate with valid username.' );
		$this->assertEquals( $this->test_user_id, $result->ID );
	}

	/**
	 * Test that admin users bypass the authentication method restriction.
	 */
	public function test_admin_bypass_authentication_method() {
		$admin_user_id = $this->factory()->user->create( array(
			'user_login' => 'admin_bypass_user_' . wp_rand(),
			'user_pass'  => 'AdminP@ss1!',
			'user_email' => 'admin_bypass_' . wp_rand() . '@example.com',
			'role'       => 'administrator',
		) );

		$admin_user = get_user_by( 'id', $admin_user_id );

		$this->set_login_method( 'email' );

		// Pass the admin WP_User object -- admin should bypass the email-only restriction.
		$result = wpum_authentication( $admin_user, $admin_user->user_login, 'AdminP@ss1!' );

		$this->assertInstanceOf( 'WP_User', $result, 'Admin should bypass authentication method.' );
		$this->assertEquals( $admin_user_id, $result->ID );
	}

	/**
	 * Test that default mode (username_email) accepts both username and email.
	 */
	public function test_default_mode_accepts_both() {
		$this->set_login_method( 'username_email' );

		// Test with username -- should pass through (not matching username or email case).
		$user = wp_authenticate( $this->test_username, $this->test_password );
		$this->assertInstanceOf( 'WP_User', $user, 'Default mode should accept username login.' );

		// Test with email.
		$user2 = wp_authenticate( $this->test_email, $this->test_password );
		$this->assertInstanceOf( 'WP_User', $user2, 'Default mode should accept email login.' );
	}
}
