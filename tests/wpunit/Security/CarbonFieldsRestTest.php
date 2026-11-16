<?php
/**
 * Carbon Fields' attachment REST route, which the image and file fields use for previews.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class CarbonFieldsRestTest extends WPUMTestCase {

	/**
	 * @var int Attachment on a private post.
	 */
	protected $private_attachment_id;

	/**
	 * @var int Attachment that isn't attached to a post.
	 */
	protected $public_attachment_id;

	public function setUp(): void {
		parent::setUp();

		$private_post_id = self::factory()->post->create( array( 'post_status' => 'private' ) );

		$this->private_attachment_id = self::factory()->attachment->create_object( 'backup.zip', $private_post_id, array(
			'post_mime_type' => 'application/zip',
			'post_type'      => 'attachment',
		) );
		$this->public_attachment_id  = self::factory()->attachment->create_object( 'avatar.jpg', 0, array(
			'post_mime_type' => 'image/jpeg',
			'post_type'      => 'attachment',
		) );

		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

	/**
	 * @param string $type  id or url.
	 * @param string $value Attachment ID or URL.
	 *
	 * @return WP_REST_Response
	 */
	protected function get_attachment( $type, $value ) {
		$params = array(
			'type'  => $type,
			'value' => (string) $value,
		);

		// Carbon Fields' handler reads $_GET rather than the request.
		$_GET = $params;

		$request = new WP_REST_Request( 'GET', '/carbon-fields/v1/attachment' );
		$request->set_query_params( $params );

		return rest_get_server()->dispatch( $request );
	}

	public function test_route_is_registered() {
		$this->assertArrayHasKey( '/carbon-fields/v1/attachment', rest_get_server()->get_routes() );
	}

	public function test_visitors_are_refused() {
		$this->assertSame( 401, $this->get_attachment( 'id', $this->private_attachment_id )->get_status() );
		$this->assertSame( 401, $this->get_attachment( 'id', $this->public_attachment_id )->get_status() );
		$this->assertSame( 401, $this->get_attachment( 'url', wp_get_attachment_url( $this->private_attachment_id ) )->get_status() );
	}

	public function test_subscriber_cannot_read_an_attachment_on_a_private_post() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 403, $this->get_attachment( 'id', $this->private_attachment_id )->get_status() );
		$this->assertSame( 403, $this->get_attachment( 'url', wp_get_attachment_url( $this->private_attachment_id ) )->get_status() );
	}

	public function test_subscriber_can_read_an_attachment_they_could_see() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 200, $this->get_attachment( 'id', $this->public_attachment_id )->get_status() );
		$this->assertSame( 200, $this->get_attachment( 'url', wp_get_attachment_url( $this->public_attachment_id ) )->get_status() );
	}

	public function test_subscriber_can_preview_a_url_that_is_not_an_attachment() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( 200, $this->get_attachment( 'url', 'https://example.com/avatar.png' )->get_status() );
	}

	public function test_subscriber_cannot_read_a_post_through_the_route() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$post_id = self::factory()->post->create( array( 'post_status' => 'private' ) );

		$this->assertSame( 403, $this->get_attachment( 'id', $post_id )->get_status() );
	}

	public function test_users_who_can_upload_files_can_read_any_attachment() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'author' ) ) );

		$response = $this->get_attachment( 'id', $this->private_attachment_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( $this->private_attachment_id, $response->get_data()['id'] );
	}
}
