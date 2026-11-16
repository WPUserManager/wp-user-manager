<?php
/**
 * Front-end requests when "Prevent site access to visitors" is on.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class PreventSiteAccessTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $page_id;

	/**
	 * @var int
	 */
	protected $reset_page_id;

	/**
	 * @var int
	 */
	protected $login_page_id;

	/**
	 * @var int
	 */
	protected $register_page_id;

	/**
	 * @var array Request body to post with the next visit.
	 */
	protected $body = array();

	/**
	 * @var string|null Where the last request was redirected.
	 */
	protected $redirected_to;

	public function setUp(): void {
		parent::setUp();

		$this->set_permalink_structure( '/%postname%/' );

		$this->page_id       = self::factory()->post->create( array(
			'post_type'    => 'page',
			'post_name'    => 'members-only',
			'post_content' => 'Members only text',
		) );
		$this->reset_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_name' => 'password-reset',
		) );

		$this->login_page_id    = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_name' => 'log-in',
		) );
		$this->register_page_id = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_name' => 'register',
		) );
		self::factory()->post->create( array(
			'post_name'    => 'board-memo',
			'post_content' => 'Members only memo',
		) );

		wpum_update_option( 'lock_complete_site', true );
		wpum_update_option( 'password_recovery_page', array( $this->reset_page_id ) );
		wpum_update_option( 'login_page', array( $this->login_page_id ) );
		wpum_update_option( 'registration_page', array( $this->register_page_id ) );
		wp_set_current_user( 0 );

		add_filter( 'wp_redirect', array( $this, 'throw_redirect' ) );
		add_filter( 'login_url', 'wpum_login_url', 10, 3 );
		add_filter( 'do_parse_request', array( $this, 'post_body' ) );

		// wp-browser's Ajax test case defines DOING_AJAX for the rest of the run.
		add_filter( 'wp_doing_ajax', '__return_false' );
		add_filter( 'wp_doing_cron', '__return_false' );
	}

	public function tearDown(): void {
		remove_filter( 'wp_redirect', array( $this, 'throw_redirect' ) );
		remove_filter( 'login_url', 'wpum_login_url', 10 );
		remove_filter( 'do_parse_request', array( $this, 'post_body' ) );
		remove_all_filters( 'wpum_prevent_entire_site_access_allowed_urls' );
		remove_filter( 'wp_doing_ajax', '__return_false' );
		remove_filter( 'wp_doing_cron', '__return_false' );
		remove_action( 'parse_request', 'wpum_prevent_entire_site', 11 );
		remove_action( 'wp', 'wpum_prevent_entire_site', 0 );
		wpum_delete_option( 'lock_complete_site' );
		wpum_delete_option( 'password_recovery_page' );
		wpum_delete_option( 'login_page' );
		wpum_delete_option( 'registration_page' );
		wpum_delete_option( 'lock_complete_site_allow_register' );
		unset( $_GET['rest_route'] );
		$_POST = array();
		$this->set_permalink_structure( '' );
		parent::tearDown();
	}

	/**
	 * go_to() empties $_POST, so put the body back just before WordPress parses the request.
	 *
	 * @param bool $parse
	 *
	 * @return bool
	 */
	public function post_body( $parse ) {
		$_POST = $this->body;

		return $parse;
	}

	public function throw_redirect( $location ) {
		// Stops the exit() after the redirect.
		$this->redirected_to = $location;
		throw new RuntimeException( 'Redirected to ' . $location );
	}

	/**
	 * Run the lock at init, then let WordPress parse and query the request.
	 *
	 * @param string $url
	 *
	 * @return string|null Where the visitor was redirected, or null if the page was served.
	 */
	protected function visit( $url ) {
		$query = (string) wp_parse_url( $url, PHP_URL_QUERY );

		$_SERVER['REQUEST_URI'] = wp_parse_url( $url, PHP_URL_PATH ) . ( $query ? '?' . $query : '' );
		$_GET                   = array();
		wp_parse_str( $query, $_GET );

		$_POST               = $this->body;
		$this->redirected_to = null;

		try {
			wpum_prevent_entire_site();
			$this->go_to( $url );
		} catch ( RuntimeException $e ) {
			if ( null === $this->redirected_to ) {
				throw $e;
			}
		}

		return $this->redirected_to;
	}

	public function test_locked_page_redirects_visitors() {
		$this->assertNotNull( $this->visit( get_permalink( $this->page_id ) ) );
	}

	public function test_rest_prefix_in_query_string_does_not_skip_the_lock() {
		$url = get_permalink( $this->page_id ) . '?probe=/wp-json/';

		$this->assertNotNull( $this->visit( $url ) );
	}

	public function test_rest_route_argument_on_a_page_does_not_skip_the_lock() {
		// Not a REST request once parsed (empty route), so it gets the page.
		$url = get_permalink( $this->page_id ) . '?rest_route=';

		$this->assertNotNull( $this->visit( $url ) );
	}

	public function test_login_field_names_do_not_unlock_a_page() {
		$this->assertNotNull( $this->post( get_permalink( $this->page_id ), array(
			'wp-submit' => '',
			'log'       => '',
		) ) );
	}

	public function test_login_form_post_to_wp_login_is_not_redirected() {
		global $pagenow;

		$original_pagenow = $pagenow;
		$pagenow          = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		wpum_update_option( 'lock_wplogin', true );

		$_SERVER['REQUEST_URI'] = '/wp-login.php';
		$_GET                   = array();
		$_POST                  = array(
			'log'       => 'someone',
			'pwd'       => 'secret',
			'wp-submit' => 'Log In',
		);

		$this->redirected_to = null;

		try {
			wpum_prevent_entire_site();
		} catch ( RuntimeException $e ) {
			if ( null === $this->redirected_to ) {
				throw $e;
			}
		} finally {
			$pagenow = $original_pagenow; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			wpum_delete_option( 'lock_wplogin' );
		}

		$this->assertNull( $this->redirected_to );
	}

	public function test_logged_in_users_are_not_redirected() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertNull( $this->visit( get_permalink( $this->page_id ) . '?probe=/wp-json/' ) );
	}

	public function test_rest_path_is_left_to_the_rest_guard() {
		$_SERVER['REQUEST_URI'] = '/wp-json/wp/v2/pages';
		$_GET                   = array();

		wpum_prevent_entire_site();

		$this->assertSame( 11, has_action( 'parse_request', 'wpum_prevent_entire_site' ) );
	}

	public function test_rest_request_detection_uses_the_path() {
		$cases = array(
			'/wp-json/'                  => true,
			'/wp-json'                   => true,
			'/wp-json/wp/v2/pages'       => true,
			'/wp-json/wpum/v1/stripe'    => true,
			'/members-only/?x=/wp-json/' => false,
			'/members-only/wp-json/'     => false,
			'/wp-jsonx/'                 => false,
			'/'                          => false,
		);

		$_GET = array();
		foreach ( $cases as $uri => $expected ) {
			$_SERVER['REQUEST_URI'] = $uri;
			$this->assertSame( $expected, wpum_is_rest_api_request(), $uri );
		}
	}

	public function test_rest_route_argument_counts_as_rest() {
		$this->set_permalink_structure( '' );

		$_SERVER['REQUEST_URI'] = '/?rest_route=/wpum/v1/stripe';
		$_GET                   = array( 'rest_route' => '/wpum/v1/stripe' );

		$this->assertTrue( wpum_is_rest_api_request() );
	}

	public function test_rest_prefix_path_is_not_rest_with_plain_permalinks() {
		$this->set_permalink_structure( '' );

		$_SERVER['REQUEST_URI'] = '/wp-json/?page_id=' . $this->page_id;
		$_GET                   = array( 'page_id' => (string) $this->page_id );

		$this->assertFalse( wpum_is_rest_api_request() );
	}

	public function test_password_reset_page_stays_open() {
		$url = add_query_arg( array(
			'login'  => 'someone',
			'key'    => 'abc123',
			'action' => 'wpum-reset',
		), get_permalink( $this->reset_page_id ) );

		$this->assertNull( $this->visit( $url ) );
	}

	public function test_child_of_password_reset_page_is_locked() {
		$child_id = self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_name'   => 'child',
			'post_parent' => $this->reset_page_id,
		) );

		$this->assertNotNull( $this->visit( get_permalink( $child_id ) ) );
	}

	public function test_password_reset_page_with_plain_permalinks() {
		$this->set_permalink_structure( '' );

		$reset_url = get_permalink( $this->reset_page_id );
		$this->assertNull( $this->visit( $reset_url ) );

		// Another page's ID that starts with the reset page's ID.
		$this->assertNotNull( $this->visit( $reset_url . '9' ) );

		// The reset page's ID with another page's slug, which WordPress serves instead.
		$this->assertNotNull( $this->visit( $reset_url . '&pagename=members-only' ) );
	}

	/**
	 * Post a body to a URL.
	 *
	 * @param string $url
	 * @param array  $body
	 *
	 * @return string|null Where the visitor was redirected, or null if the page was served.
	 */
	protected function post( $url, $body ) {
		$this->body = $body;

		try {
			return $this->visit( $url );
		} finally {
			$this->body = array();
		}
	}

	/**
	 * Bodies that make WordPress serve other content at the URL they are posted to.
	 *
	 * @return array
	 */
	protected function content_picking_bodies() {
		return array(
			'pagename' => array( 'pagename' => 'members-only' ),
			'page_id'  => array(
				'pagename' => '',
				'page_id'  => (string) $this->page_id,
			),
			'name'     => array(
				'pagename' => '',
				'name'     => 'board-memo',
			),
			'feed'     => array(
				'pagename' => '',
				'feed'     => 'rss2',
			),
		);
	}

	public function test_login_page_stays_open() {
		$this->assertNull( $this->visit( wp_login_url() ) );
	}

	public function test_login_form_post_to_login_page_stays_open() {
		$this->assertNull( $this->post( wp_login_url(), array(
			'username'     => 'someone',
			'password'     => 'secret',
			'wpum_form'    => 'login',
			'submit_login' => 'Login',
		) ) );
	}

	public function test_query_vars_in_body_do_not_unlock_the_login_page() {
		foreach ( $this->content_picking_bodies() as $label => $body ) {
			$this->assertNotNull( $this->post( wp_login_url(), $body ), $label );
		}
	}

	public function test_query_vars_in_body_do_not_unlock_the_registration_page() {
		wpum_update_option( 'lock_complete_site_allow_register', true );
		$register_url = get_permalink( $this->register_page_id );

		$this->assertNull( $this->visit( $register_url ) );

		foreach ( $this->content_picking_bodies() as $label => $body ) {
			$this->assertNotNull( $this->post( $register_url, $body ), $label );
		}
	}

	public function test_registration_page_is_locked_unless_registration_is_allowed() {
		$this->assertNotNull( $this->visit( get_permalink( $this->register_page_id ) ) );
	}

	public function test_query_vars_in_body_do_not_unlock_an_allowed_url() {
		$home = home_url( '/' );
		add_filter( 'wpum_prevent_entire_site_access_allowed_urls', function () use ( $home ) {
			return array( $home );
		} );

		$this->assertNull( $this->visit( $home ) );

		foreach ( $this->content_picking_bodies() as $label => $body ) {
			$this->assertNotNull( $this->post( $home, $body ), $label );
		}
	}
}
