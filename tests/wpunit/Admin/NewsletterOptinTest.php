<?php
/**
 * Tests for the admin newsletter opt-in.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class NewsletterOptinTest extends WPUMTestCase {

	/**
	 * @var WPUM_Newsletter
	 */
	private $newsletter;

	/**
	 * Requests caught by the HTTP mock.
	 *
	 * @var array
	 */
	private $requests = array();

	/**
	 * Response the HTTP mock returns.
	 *
	 * @var array|WP_Error
	 */
	private $response;

	public function _setUp() {
		parent::_setUp();

		require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-newsletter.php';
		$this->newsletter = new WPUM_Newsletter();

		delete_option( WPUM_Newsletter::OPTION );
		update_option( 'wpum_activation_date', strtotime( '-30 days' ) );

		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$_GET['page'] = 'wpum-settings';

		$this->requests = array();
		$this->response = $this->http_response( 302, '' );
		add_filter( 'pre_http_request', array( $this, 'mock_http' ), 10, 3 );
	}

	public function _tearDown() {
		remove_filter( 'pre_http_request', array( $this, 'mock_http' ), 10 );
		remove_all_filters( 'wpum_show_newsletter_optin' );
		unset( $_GET['page'] );

		parent::_tearDown();
	}

	/**
	 * Catch requests to the newsletter endpoint.
	 */
	public function mock_http( $preempt, $args, $url ) {
		if ( WPUM_Newsletter::SUBSCRIBE_URL !== $url ) {
			return $preempt;
		}

		$this->requests[] = $args;

		return $this->response;
	}

	private function http_response( $code, $body ) {
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
		);
	}

	public function test_redirect_to_thank_you_page_is_success() {
		$this->assertTrue( $this->newsletter->subscribe( 'jane@example.com', 'Jane' ) );

		$this->assertCount( 1, $this->requests );
		$args = $this->requests[0];
		$this->assertSame( 0, $args['redirection'] );
		$this->assertSame( 'jane@example.com', $args['body']['email'] );
		$this->assertSame( 'Jane', $args['body']['name'] );
		$this->assertSame( WPUM_Newsletter::LIST_ID, $args['body']['list'] );
		$this->assertSame( 'plugin', $args['body']['Source'] );
		$this->assertSame( 'yes', $args['body']['subform'] );
	}

	public function test_subscribed_page_is_success() {
		$this->response = $this->http_response( 200, "<title>You're subscribed!</title><h2>You're subscribed!</h2>" );

		$this->assertTrue( $this->newsletter->subscribe( 'jane@example.com' ) );
	}

	public function test_confirmation_email_page_is_success() {
		$this->response = $this->http_response( 200, '<h2><span>Thank you, a confirmation email has been sent to you.</span></h2>' );

		$this->assertTrue( $this->newsletter->subscribe( 'jane@example.com' ) );
	}

	public function test_sendy_error_is_returned() {
		$this->response = $this->http_response( 200, '<title>Email address is bounced.</title><h2>Email address is bounced.</h2>' );

		$result = $this->newsletter->subscribe( 'jane@example.com' );

		$this->assertWPError( $result );
		$this->assertSame( 'Email address is bounced.', $result->get_error_message() );
	}

	public function test_transport_failure_is_an_error() {
		$this->response = new WP_Error( 'http_request_failed', 'timeout' );

		$this->assertWPError( $this->newsletter->subscribe( 'jane@example.com' ) );
	}

	public function test_invalid_email_is_not_sent() {
		$this->assertWPError( $this->newsletter->subscribe( 'not-an-email' ) );
		$this->assertCount( 0, $this->requests );
	}

	public function test_notice_shows_on_wpum_screens_after_a_week() {
		$this->assertTrue( $this->newsletter->should_show_notice() );
	}

	public function test_notice_waits_a_week_after_install() {
		update_option( 'wpum_activation_date', strtotime( '-2 days' ) );

		$this->assertFalse( $this->newsletter->should_show_notice() );
	}

	public function test_notice_only_shows_on_wpum_screens() {
		$_GET['page'] = 'some-other-plugin';
		$this->assertFalse( $this->newsletter->should_show_notice() );

		$_GET['page'] = 'wpum-getting-started';
		$this->assertFalse( $this->newsletter->should_show_notice() );
	}

	public function test_notice_needs_manage_options() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertFalse( $this->newsletter->should_show_notice() );
	}

	public function test_notice_stops_once_answered() {
		update_option( WPUM_Newsletter::OPTION, 'dismissed' );
		$this->assertFalse( $this->newsletter->should_show_notice() );

		update_option( WPUM_Newsletter::OPTION, 'subscribed' );
		$this->assertFalse( $this->newsletter->should_show_notice() );
	}

	public function test_filter_turns_the_offer_off() {
		add_filter( 'wpum_show_newsletter_optin', '__return_false' );

		$this->assertFalse( $this->newsletter->should_show_notice() );
		$this->assertFalse( $this->newsletter->can_ask() );
	}

	public function test_form_is_prefilled_with_the_admin_email() {
		$user = wp_get_current_user();

		ob_start();
		$this->newsletter->render_form();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'value="' . esc_attr( $user->user_email ) . '"', $html );
		$this->assertStringContainsString( 'name="_wpnonce"', $html );
	}

	public function test_ajax_subscribe_records_the_answer() {
		$response = $this->ajax(
			'handle_subscribe',
			array(
				'email' => 'jane@example.com',
				'name'  => 'Jane',
			)
		);

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'subscribed', get_option( WPUM_Newsletter::OPTION ) );
	}

	public function test_failed_ajax_subscribe_keeps_asking() {
		$this->response = $this->http_response( 200, '<h2>Email address is invalid.</h2>' );

		$response = $this->ajax( 'handle_subscribe', array( 'email' => 'jane@example.com' ) );

		$this->assertFalse( $response['success'] );
		$this->assertFalse( get_option( WPUM_Newsletter::OPTION ) );
	}

	public function test_ajax_dismiss_records_the_answer() {
		$response = $this->ajax( 'handle_dismiss' );

		$this->assertTrue( $response['success'] );
		$this->assertSame( 'dismissed', get_option( WPUM_Newsletter::OPTION ) );
		$this->assertCount( 0, $this->requests );
	}

	/**
	 * Call a handler as admin-ajax would and return its decoded JSON.
	 */
	private function ajax( $method, $post = array() ) {
		$_POST = $_REQUEST = array_merge( $post, array( '_wpnonce' => wp_create_nonce( WPUM_Newsletter::NONCE ) ) );

		add_filter( 'wp_doing_ajax', '__return_true' );
		add_filter( 'wp_die_ajax_handler', array( $this, 'die_handler' ) );

		ob_start();
		try {
			$this->newsletter->$method();
		} catch ( WPAjaxDieContinueException $e ) {
			unset( $e );
		} finally {
			$output = ob_get_clean();
			remove_filter( 'wp_doing_ajax', '__return_true' );
			remove_filter( 'wp_die_ajax_handler', array( $this, 'die_handler' ) );
		}

		return json_decode( $output, true );
	}

	public function die_handler() {
		return array( $this, 'throw_on_die' );
	}

	public function throw_on_die() {
		throw new WPAjaxDieContinueException();
	}
}

if ( ! class_exists( 'WPAjaxDieContinueException' ) ) {
	class WPAjaxDieContinueException extends Exception {} // phpcs:ignore
}
