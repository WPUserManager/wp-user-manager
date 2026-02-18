<?php
/**
 * Tests for WPUM email tag replacement functions.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailTagsTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $test_user_id;

	public function _setUp() {
		parent::_setUp();

		$this->test_user_id = $this->factory()->user->create( array(
			'user_login' => 'email_tag_user_' . wp_rand(),
			'user_pass'  => 'StrongP@ss1!',
			'user_email' => 'emailtag_' . wp_rand() . '@example.com',
			'first_name' => 'TagFirst',
			'last_name'  => 'TagLast',
		) );
	}

	/**
	 * Test that {sitename} tag is replaced with the blog name.
	 */
	public function test_sitename_tag_replaced() {
		$result = wpum_email_tag_sitename( $this->test_user_id );

		$this->assertEquals( esc_html( get_bloginfo( 'name' ) ), $result );
	}

	/**
	 * Test that {username} tag is replaced with the user's login name.
	 */
	public function test_username_tag_replaced() {
		$user   = get_user_by( 'id', $this->test_user_id );
		$result = wpum_email_tag_username( $this->test_user_id );

		$this->assertEquals( $user->user_login, $result );
	}

	/**
	 * Test that {email} tag is replaced with the user's email.
	 */
	public function test_email_tag_replaced() {
		$user   = get_user_by( 'id', $this->test_user_id );
		$result = wpum_email_tag_email( $this->test_user_id );

		$this->assertEquals( $user->user_email, $result );
	}

	/**
	 * Test that {login_page_url} tag returns a URL.
	 */
	public function test_login_page_url_tag_replaced() {
		$page_id = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_title'  => 'Login Page',
			'post_status' => 'publish',
		) );

		$filter = function () use ( $page_id ) {
			return array( $page_id );
		};
		add_filter( 'wpum_get_option_login_page', $filter );

		$result = wpum_email_tag_login_page_url( $this->test_user_id );

		remove_filter( 'wpum_get_option_login_page', $filter );

		$this->assertNotEmpty( $result, 'login_page_url tag should return a non-empty value.' );
	}

	/**
	 * Test that {firstname} tag is replaced.
	 */
	public function test_firstname_tag_replaced() {
		$result = wpum_email_tag_firstname( $this->test_user_id );

		$this->assertEquals( 'TagFirst', $result );
	}

	/**
	 * Test that {lastname} tag is replaced.
	 */
	public function test_lastname_tag_replaced() {
		$result = wpum_email_tag_lastname( $this->test_user_id );

		$this->assertEquals( 'TagLast', $result );
	}

	/**
	 * Test that {website} tag returns home URL.
	 */
	public function test_website_tag_replaced() {
		$result = wpum_email_tag_website( $this->test_user_id );

		$this->assertEquals( home_url(), $result );
	}
}
