<?php
/**
 * Licenses are activated for a stable site URL, and moved at most once a day.
 *
 * Before 2.10.2 a license was deactivated and re-activated on every admin and AJAX
 * request whenever home_url() differed from the cached URL, so multilingual and
 * multi-domain sites hammered wpusermanager.com all day.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class LicenseSiteUrlTest extends WPUMTestCase {

	/**
	 * EDD requests made, in order, as action => url.
	 *
	 * @var array[]
	 */
	protected $requests = array();

	/**
	 * The home_url filter a test added, if any.
	 *
	 * @var callable|null
	 */
	protected $home_url_filter;

	public function setUp(): void {
		parent::setUp();
		$this->requests = array();
		update_option( '_wpum_test_addon_license_key', 'test-key' );
		delete_site_transient( 'wpum_test_addon_license_data' );
		delete_site_transient( 'wpum_test_addon_license_moved' );
		add_filter( 'pre_http_request', array( $this, 'fake_edd' ), 10, 3 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'fake_edd' ), 10 );
		if ( $this->home_url_filter ) {
			remove_filter( 'home_url', $this->home_url_filter );
		}
		delete_option( '_wpum_test_addon_license_key' );
		delete_site_transient( 'wpum_test_addon_license_data' );
		delete_site_transient( 'wpum_test_addon_license_moved' );
		parent::tearDown();
	}

	public function fake_edd( $preempt, $args, $url ) {
		if ( 'https://wpusermanager.com' !== $url ) {
			return $preempt;
		}

		$this->requests[] = array( $args['body']['edd_action'], $args['body']['url'] );

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array(
				'success' => true,
				'license' => 'deactivate_license' === $args['body']['edd_action'] ? 'deactivated' : 'valid',
				'expires' => '2027-03-01 23:59:59',
			) ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}

	protected function get_license_data() {
		$license = new WPUM_License( WPUM_PLUGIN_FILE, 'Test Addon', '123', '1.0.0', 'WP User Manager' );
		$method  = new ReflectionMethod( $license, 'get_license_data' );
		$method->setAccessible( true );

		return $method->invoke( $license );
	}

	protected function cache_license_for( $url ) {
		set_site_transient( 'wpum_test_addon_license_data', array(
			'url'    => $url,
			'status' => 'valid',
		), HOUR_IN_SECONDS * 12 );
	}

	protected function home() {
		return untrailingslashit( get_option( 'home' ) );
	}

	public function test_language_filtered_home_url_is_ignored() {
		$this->cache_license_for( $this->home() );

		foreach ( array( 'fr', 'es', 'en' ) as $lang ) {
			if ( $this->home_url_filter ) {
				remove_filter( 'home_url', $this->home_url_filter );
			}
			$this->home_url_filter = function ( $url ) use ( $lang ) {
				return trailingslashit( $url ) . $lang;
			};
			add_filter( 'home_url', $this->home_url_filter );

			$this->assertSame( 'valid', $this->get_license_data()['status'] );
		}

		$this->assertSame( array(), $this->requests );
	}

	/**
	 * @dataProvider same_site_variants
	 */
	public function test_scheme_www_and_trailing_slash_are_the_same_site( $variant ) {
		$host = preg_replace( '#^https?://#', '', $this->home() );
		$this->cache_license_for( sprintf( $variant, $host ) );

		$this->get_license_data();

		$this->assertSame( array(), $this->requests );
	}

	public function same_site_variants() {
		return array(
			'https'          => array( 'https://%s' ),
			'http'           => array( 'http://%s' ),
			'www'            => array( 'https://www.%s' ),
			'trailing slash' => array( 'http://%s/' ),
		);
	}

	public function test_moved_site_moves_the_license_once() {
		$this->cache_license_for( 'https://staging.example.org' );

		$data = $this->get_license_data();

		$this->assertSame(
			array(
				array( 'deactivate_license', 'https://staging.example.org' ),
				array( 'activate_license', $this->home() ),
			),
			$this->requests
		);
		$this->assertSame( $this->home(), $data['url'] );
		$this->assertSame( 'valid', $data['status'] );
	}

	public function test_license_moves_at_most_once_a_day() {
		$this->cache_license_for( 'https://staging.example.org' );
		$this->get_license_data();

		// Something puts the other URL back, as a site that changes URL per request would.
		$this->cache_license_for( 'https://other.example.org' );
		$data = $this->get_license_data();

		$this->assertCount( 2, $this->requests );
		$this->assertSame( 'https://other.example.org', $data['url'] );
	}

	public function test_failed_move_is_not_retried_on_the_next_request() {
		$this->cache_license_for( 'https://staging.example.org' );

		remove_filter( 'pre_http_request', array( $this, 'fake_edd' ), 10 );
		add_filter( 'pre_http_request', array( $this, 'fail_edd' ), 10, 3 );

		$this->get_license_data();
		$this->get_license_data();

		remove_filter( 'pre_http_request', array( $this, 'fail_edd' ), 10 );

		$this->assertCount( 2, $this->requests );
	}

	public function fail_edd( $preempt, $args, $url ) {
		if ( 'https://wpusermanager.com' !== $url ) {
			return $preempt;
		}

		$this->requests[] = array( $args['body']['edd_action'], $args['body']['url'] );

		return new WP_Error( 'http_request_failed', 'cURL error 28: Operation timed out' );
	}
}
