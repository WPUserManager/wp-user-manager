<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * WPUM form pages must be kept out of page and browser caches.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/117
 * @see https://github.com/WPUserManager/wp-user-manager/issues/304
 */
class PageCachingTest extends WPUMTestCase {

	/**
	 * @var array Original core page options, restored after each test.
	 */
	private $original = array();

	public function _setUp() {
		parent::_setUp();

		foreach ( array( 'login_page', 'registration_page', 'password_recovery_page', 'account_page' ) as $option ) {
			$this->original[ $option ] = wpum_get_option( $option );
		}
	}

	public function _tearDown() {
		foreach ( $this->original as $option => $value ) {
			wpum_update_option( $option, $value );
		}
		remove_all_filters( 'wpum_is_uncacheable_request' );
		parent::_tearDown();
	}

	/**
	 * Create a page and load it as the main query.
	 *
	 * @param string $content Page content.
	 *
	 * @return int Page ID.
	 */
	private function visit_page( $content = '' ) {
		$page_id = $this->factory()->post->create( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => $content,
		) );

		$this->go_to( get_permalink( $page_id ) );

		return $page_id;
	}

	public function core_page_options() {
		return array(
			'login'    => array( 'login_page' ),
			'register' => array( 'registration_page' ),
			'password' => array( 'password_recovery_page' ),
			'account'  => array( 'account_page' ),
		);
	}

	/**
	 * @dataProvider core_page_options
	 */
	public function test_core_form_pages_are_uncacheable( $option ) {
		$page_id = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );
		wpum_update_option( $option, array( $page_id ) );

		$this->go_to( get_permalink( $page_id ) );

		$this->assertTrue( wpum_is_uncacheable_request() );
	}

	public function test_ordinary_page_is_cacheable() {
		$this->visit_page( 'Hello' );

		$this->assertFalse( wpum_is_uncacheable_request() );
	}

	public function test_profile_page_stays_cacheable() {
		$this->visit_page( '[wpum_profile]' );

		$this->assertFalse( wpum_is_uncacheable_request() );
	}

	public function test_page_with_a_form_shortcode_is_uncacheable() {
		$this->visit_page( 'Sign in: [wpum_login_form]' );

		$this->assertTrue( wpum_is_uncacheable_request() );
	}

	public function test_password_reset_step_is_uncacheable() {
		$page_id = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );
		wpum_update_option( 'password_recovery_page', array( $page_id ) );

		$this->go_to( add_query_arg( 'step', 'reset', get_permalink( $page_id ) ) );

		$this->assertTrue( wpum_is_uncacheable_request() );
	}

	public function test_filter_can_opt_out() {
		$this->visit_page( '[wpum_register]' );
		add_filter( 'wpum_is_uncacheable_request', '__return_false' );

		$this->assertFalse( wpum_is_uncacheable_request() );
	}

	public function test_runs_early_on_template_redirect() {
		$this->assertSame( 0, has_action( 'template_redirect', 'wpum_prevent_caching_form_pages' ) );
	}
}
