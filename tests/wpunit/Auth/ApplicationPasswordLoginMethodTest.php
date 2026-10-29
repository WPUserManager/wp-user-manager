<?php
/**
 * Application passwords work for non-admins whatever the login method setting.
 *
 * Core checks an application password on `authenticate` before WPUM's login method
 * filter runs. WPUM used to check the same password again as the account password,
 * which rejected it for users without manage_options when the login method was
 * "Email only" or "Username only" (for example over XML-RPC).
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/472
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ApplicationPasswordLoginMethodTest extends WPUMTestCase {

	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $app_password;

	/**
	 * @var mixed
	 */
	protected $original_method;

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WP_Application_Passwords' ) ) {
			$this->markTestSkipped( 'Application passwords need WordPress 5.6 or later.' );
		}

		$this->original_method = wpum_get_option( 'login_method' );

		// Application passwords only apply to API requests, and need HTTPS or a local site.
		add_filter( 'application_password_is_api_request', '__return_true' );
		add_filter( 'wp_is_application_passwords_available', '__return_true' );
		add_filter( 'wp_is_application_passwords_available_for_user', '__return_true' );

		$this->user = self::factory()->user->create_and_get(
			array(
				'user_login' => 'app_pass_editor_' . wp_rand(),
				'user_email' => 'app_pass_' . wp_rand() . '@example.com',
				'user_pass'  => 'AccountPassword1!',
				'role'       => 'editor',
			)
		);

		list( $this->app_password ) = WP_Application_Passwords::create_new_application_password( $this->user->ID, array( 'name' => 'XML-RPC client' ) );
	}

	public function _tearDown() {
		wpum_update_option( 'login_method', $this->original_method );
		remove_filter( 'application_password_is_api_request', '__return_true' );
		remove_filter( 'wp_is_application_passwords_available', '__return_true' );
		remove_filter( 'wp_is_application_passwords_available_for_user', '__return_true' );

		parent::_tearDown();
	}

	public function test_application_password_works_with_email_only_login() {
		wpum_update_option( 'login_method', 'email' );

		$result = wp_authenticate( $this->user->user_email, $this->app_password );

		$this->assertInstanceOf( 'WP_User', $result );
		$this->assertSame( $this->user->ID, $result->ID );
	}

	public function test_application_password_works_with_username_only_login() {
		wpum_update_option( 'login_method', 'username' );

		$result = wp_authenticate( $this->user->user_login, $this->app_password );

		$this->assertInstanceOf( 'WP_User', $result );
		$this->assertSame( $this->user->ID, $result->ID );
	}

	public function test_a_wrong_application_password_is_still_rejected() {
		wpum_update_option( 'login_method', 'email' );

		$this->assertWPError( wp_authenticate( $this->user->user_email, 'abcd efgh ijkl mnop qrst uvwx' ) );
	}

	public function test_email_only_still_rejects_a_username_with_the_account_password() {
		wpum_update_option( 'login_method', 'email' );
		remove_filter( 'application_password_is_api_request', '__return_true' );

		$this->assertWPError( wp_authenticate( $this->user->user_login, 'AccountPassword1!' ) );
	}

	public function test_only_the_user_core_authenticated_is_trusted() {
		$other = self::factory()->user->create();

		wpum_authenticated_by_application_password( $this->user );

		$this->assertTrue( wpum_authenticated_by_application_password( $this->user->ID ) );
		$this->assertFalse( wpum_authenticated_by_application_password( $other ) );
	}
}
