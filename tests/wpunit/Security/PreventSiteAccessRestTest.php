<?php
/**
 * REST API requests when "Prevent site access to visitors" is on.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class PreventSiteAccessRestTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $post_id;

	public function setUp(): void {
		parent::setUp();

		$this->post_id = self::factory()->post->create( array( 'post_content' => 'Members only text' ) );
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		wpum_delete_option( 'lock_complete_site' );
		parent::tearDown();
	}

	protected function get( $route ) {
		return rest_get_server()->dispatch( new WP_REST_Request( 'GET', $route ) );
	}

	public function test_logged_out_rest_request_is_refused_when_site_is_locked() {
		wpum_update_option( 'lock_complete_site', true );

		$response = $this->get( '/wp/v2/posts/' . $this->post_id );

		$this->assertSame( 401, $response->get_status() );
		$this->assertSame( 'rest_not_logged_in', $response->get_data()['code'] );
	}

	public function test_logged_in_rest_request_is_allowed_when_site_is_locked() {
		wpum_update_option( 'lock_complete_site', true );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$response = $this->get( '/wp/v2/posts/' . $this->post_id );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_rest_is_unchanged_when_site_is_not_locked() {
		$response = $this->get( '/wp/v2/posts/' . $this->post_id );

		$this->assertSame( 200, $response->get_status() );
	}

	public function test_allowed_routes_stay_open_when_site_is_locked() {
		wpum_update_option( 'lock_complete_site', true );

		$allowed = apply_filters( 'wpum_prevent_entire_site_rest_allowed_routes', array( '/wpum/v1/stripe' ) );
		$this->assertContains( '/wpum/v1/stripe', $allowed );

		$request = new WP_REST_Request( 'POST', '/wpum/v1/stripe' );
		$this->assertNull( wpum_prevent_entire_site_rest( null, rest_get_server(), $request ) );
	}
}
