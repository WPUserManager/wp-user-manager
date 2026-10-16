<?php
/**
 * Logged in visitors on the login page follow its redirect_to parameter.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/513
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class LoggedInLoginRedirectTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $login_page_id;

	/**
	 * @var mixed Original login page option, restored after each test.
	 */
	protected $original_login_page;

	/**
	 * @var string|null Where the last request was redirected.
	 */
	protected $redirected_to;

	public function setUp(): void {
		parent::setUp();

		$this->original_login_page = wpum_get_option( 'login_page' );

		$this->login_page_id = self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_name'   => 'login',
		) );

		wpum_update_option( 'login_page', array( $this->login_page_id ) );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		add_filter( 'wp_redirect', array( $this, 'throw_redirect' ) );
	}

	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'throw_redirect' ) );
		remove_all_filters( 'wpum_shortcode_logged_in_override' );
		wpum_update_option( 'login_page', $this->original_login_page );
		$_GET = array();
		parent::tearDown();
	}

	public function throw_redirect( $location ) {
		// Stops the exit() after the redirect.
		$this->redirected_to = $location;
		throw new RuntimeException( 'Redirected to ' . $location );
	}

	/**
	 * Load a page with the given query args and run the redirect handler.
	 *
	 * @param int   $page_id
	 * @param array $args
	 *
	 * @return string|null Where the visitor was redirected, or null if the page was served.
	 */
	protected function visit( $page_id, $args = array() ) {
		$this->go_to( add_query_arg( array_map( 'rawurlencode', $args ), get_permalink( $page_id ) ) );

		$_GET                = $args;
		$this->redirected_to = null;

		try {
			wpum_redirect_logged_in_from_login_page();
		} catch ( RuntimeException $e ) {
			if ( null === $this->redirected_to ) {
				throw $e;
			}
		}

		return $this->redirected_to;
	}

	public function test_handler_runs_on_template_redirect() {
		$this->assertNotFalse( has_action( 'template_redirect', 'wpum_redirect_logged_in_from_login_page' ) );
	}

	public function test_local_redirect_to_is_followed() {
		$target = admin_url( 'edit.php' );

		$this->assertSame( $target, $this->visit( $this->login_page_id, array( 'redirect_to' => $target ) ) );
	}

	public function test_external_redirect_to_shows_the_login_page() {
		$this->assertNull( $this->visit( $this->login_page_id, array( 'redirect_to' => 'https://example.org/phish' ) ) );
	}

	public function test_reauth_shows_the_login_page() {
		$args = array(
			'redirect_to' => admin_url(),
			'reauth'      => '1',
		);

		$this->assertNull( $this->visit( $this->login_page_id, $args ) );
	}

	public function test_no_redirect_to_shows_the_login_page() {
		$this->assertNull( $this->visit( $this->login_page_id ) );
	}

	public function test_logged_out_visitors_are_not_redirected() {
		wp_set_current_user( 0 );

		$this->assertNull( $this->visit( $this->login_page_id, array( 'redirect_to' => admin_url() ) ) );
	}

	public function test_other_pages_are_not_redirected() {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$this->assertNull( $this->visit( $page_id, array( 'redirect_to' => admin_url() ) ) );
	}

	public function test_logged_in_override_shows_the_login_page() {
		add_filter( 'wpum_shortcode_logged_in_override', '__return_true' );

		$this->assertNull( $this->visit( $this->login_page_id, array( 'redirect_to' => admin_url() ) ) );
	}
}
