<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Logged in users can be sent away from the login and registration pages.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/50
 */
class LoggedInRedirectTest extends WPUMTestCase {

	/**
	 * @var array
	 */
	private $original = array();

	/**
	 * @var int
	 */
	private $login_page;

	/**
	 * @var int
	 */
	private $register_page;

	/**
	 * @var int
	 */
	private $target_page;

	public function _setUp() {
		parent::_setUp();

		foreach ( array( 'login_page', 'registration_page', 'logged_in_redirect' ) as $option ) {
			$this->original[ $option ] = wpum_get_option( $option );
		}

		$this->login_page    = $this->factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->register_page = $this->factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->target_page   = $this->factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );

		wpum_update_option( 'login_page', array( $this->login_page ) );
		wpum_update_option( 'registration_page', array( $this->register_page ) );

		add_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );
	}

	public function _tearDown() {
		remove_filter( 'wp_redirect', array( $this, 'stop_redirect' ) );

		foreach ( $this->original as $option => $value ) {
			wpum_update_option( $option, $value );
		}

		parent::_tearDown();
	}

	/**
	 * Stop the redirect before exit() so the test can see where it went.
	 *
	 * @param string $location
	 *
	 * @throws Exception Always, carrying the location.
	 */
	public function stop_redirect( $location ) {
		throw new Exception( $location );
	}

	/**
	 * Visit a page and run the handler.
	 *
	 * @param int $page_id
	 *
	 * @return string|null Redirect location, or null if there was none.
	 */
	private function visit( $page_id ) {
		$this->go_to( get_permalink( $page_id ) );

		try {
			wpum_redirect_logged_in_users();
		} catch ( Exception $e ) {
			return $e->getMessage();
		}

		return null;
	}

	private function log_in() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );
	}

	public function test_logged_in_user_is_redirected_from_the_login_page() {
		wpum_update_option( 'logged_in_redirect', array( $this->target_page ) );
		$this->log_in();

		$this->assertSame( get_permalink( $this->target_page ), $this->visit( $this->login_page ) );
	}

	public function test_logged_in_user_is_redirected_from_the_registration_page() {
		wpum_update_option( 'logged_in_redirect', array( $this->target_page ) );
		$this->log_in();

		$this->assertSame( get_permalink( $this->target_page ), $this->visit( $this->register_page ) );
	}

	public function test_homepage_option_redirects_home() {
		wpum_update_option( 'logged_in_redirect', array( 'hp' ) );
		$this->log_in();

		$this->assertSame( home_url(), $this->visit( $this->login_page ) );
	}

	public function test_logged_out_visitor_is_not_redirected() {
		wpum_update_option( 'logged_in_redirect', array( $this->target_page ) );
		wp_set_current_user( 0 );

		$this->assertNull( $this->visit( $this->login_page ) );
	}

	public function test_no_redirect_when_the_setting_is_empty() {
		wpum_update_option( 'logged_in_redirect', '' );
		$this->log_in();

		$this->assertNull( $this->visit( $this->login_page ) );
	}

	public function test_other_pages_are_not_redirected() {
		wpum_update_option( 'logged_in_redirect', array( $this->target_page ) );
		$this->log_in();

		$this->assertNull( $this->visit( $this->target_page ) );
	}

	public function test_never_redirects_a_page_to_itself() {
		wpum_update_option( 'logged_in_redirect', array( $this->login_page ) );
		$this->log_in();

		$this->assertNull( $this->visit( $this->login_page ) );
	}
}
