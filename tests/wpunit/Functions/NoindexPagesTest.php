<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * WPUM's utility pages are noindexed and left out of sitemaps.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/514
 */
class NoindexPagesTest extends WPUMTestCase {

	/**
	 * @var array Original option values, restored after each test.
	 */
	private $original = array();

	/**
	 * @var int[] Core page IDs keyed by option name.
	 */
	private $pages = array();

	public function _setUp() {
		parent::_setUp();

		foreach ( array( 'login_page', 'registration_page', 'password_recovery_page', 'account_page', 'profile_page', 'guests_can_view_profiles' ) as $option ) {
			$this->original[ $option ] = wpum_get_option( $option );
		}

		foreach ( array( 'login_page', 'registration_page', 'password_recovery_page', 'account_page', 'profile_page' ) as $option ) {
			$this->pages[ $option ] = $this->factory()->post->create( array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			) );
			wpum_update_option( $option, array( $this->pages[ $option ] ) );
		}

		wpum_update_option( 'guests_can_view_profiles', true );
		update_option( 'blog_public', '1' );
		update_option( 'wpum_permalink', 'user_id' );
		wp_set_current_user( 0 );
	}

	public function _tearDown() {
		foreach ( $this->original as $option => $value ) {
			wpum_update_option( $option, $value );
		}
		remove_all_filters( 'wpum_noindex_pages' );
		parent::_tearDown();
	}

	/**
	 * The robots meta tag for the current request.
	 *
	 * @return string
	 */
	private function robots_meta() {
		ob_start();
		wp_robots();

		return (string) ob_get_clean();
	}

	/**
	 * Load the profile page for a member.
	 *
	 * @param int $user_id
	 */
	private function visit_member_profile( $user_id ) {
		$this->go_to( get_permalink( $this->pages['profile_page'] ) );
		set_query_var( 'profile', (string) $user_id );
	}

	public function core_page_options() {
		return array(
			'login'    => array( 'login_page' ),
			'register' => array( 'registration_page' ),
			'password' => array( 'password_recovery_page' ),
			'account'  => array( 'account_page' ),
			'profile'  => array( 'profile_page' ),
		);
	}

	/**
	 * @dataProvider core_page_options
	 */
	public function test_core_page_is_noindexed( $option ) {
		$this->go_to( get_permalink( $this->pages[ $option ] ) );

		$this->assertStringContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_normal_page_is_indexable() {
		$page_id = $this->factory()->post->create( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '[wpum_login_form]',
		) );

		$this->go_to( get_permalink( $page_id ) );

		$this->assertStringNotContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_member_directory_is_indexable() {
		$directory_id = $this->factory()->post->create( array(
			'post_type'   => 'wpum_directory',
			'post_status' => 'publish',
		) );
		$page_id      = $this->factory()->post->create( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '[wpum_user_directory id="' . $directory_id . '"]',
		) );

		$this->go_to( get_permalink( $page_id ) );

		$this->assertStringNotContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_account_tab_is_noindexed() {
		$this->go_to( get_permalink( $this->pages['account_page'] ) );
		set_query_var( 'tab', 'password' );

		$this->assertStringContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_member_profile_is_indexable_when_guests_can_view_profiles() {
		$this->visit_member_profile( $this->factory()->user->create() );

		$this->assertStringNotContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_member_profile_is_noindexed_when_guests_cannot_view_profiles() {
		wpum_update_option( 'guests_can_view_profiles', false );

		$this->visit_member_profile( $this->factory()->user->create() );

		$this->assertStringContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_unknown_member_profile_is_noindexed() {
		$this->visit_member_profile( 999999 );

		$this->assertStringContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_filter_can_turn_it_off() {
		add_filter( 'wpum_noindex_pages', '__return_empty_array' );

		$this->go_to( get_permalink( $this->pages['login_page'] ) );

		$this->assertStringNotContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_filter_can_change_the_pages() {
		$login_page_id = $this->pages['login_page'];
		add_filter(
			'wpum_noindex_pages',
			function ( $page_ids ) use ( $login_page_id ) {
				return array_diff( $page_ids, array( $login_page_id ) );
			}
		);

		$this->go_to( get_permalink( $login_page_id ) );
		$this->assertStringNotContainsString( 'noindex', $this->robots_meta() );

		$this->go_to( get_permalink( $this->pages['registration_page'] ) );
		$this->assertStringContainsString( 'noindex', $this->robots_meta() );
	}

	public function test_core_pages_are_left_out_of_the_core_sitemap() {
		$normal_page_id = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );

		$urls = wp_list_pluck( wp_sitemaps_get_server()->registry->get_provider( 'posts' )->get_url_list( 1, 'page' ), 'loc' );

		$this->assertContains( get_permalink( $normal_page_id ), $urls );
		foreach ( $this->pages as $option => $page_id ) {
			$this->assertNotContains( get_permalink( $page_id ), $urls, $option );
		}
	}

	public function test_yoast_robots_are_noindexed_on_core_pages() {
		$this->go_to( get_permalink( $this->pages['login_page'] ) );

		$robots = apply_filters( 'wpseo_robots_array', array( 'index' => 'index', 'follow' => 'follow' ) );

		$this->assertSame( 'noindex', $robots['index'] );
		$this->assertSame( 'follow', $robots['follow'] );
	}

	public function test_rank_math_robots_are_noindexed_on_core_pages() {
		$this->go_to( get_permalink( $this->pages['account_page'] ) );

		$robots = apply_filters( 'rank_math/frontend/robots', array( 'index' => 'index', 'follow' => 'follow' ) );

		$this->assertSame( 'noindex', $robots['index'] );
	}

	public function test_seo_plugin_robots_are_unchanged_on_other_pages() {
		$this->go_to( get_permalink( $this->factory()->post->create( array( 'post_type' => 'page' ) ) ) );

		$robots = array( 'index' => 'index', 'follow' => 'follow' );

		$this->assertSame( $robots, apply_filters( 'wpseo_robots_array', $robots ) );
		$this->assertSame( $robots, apply_filters( 'rank_math/frontend/robots', $robots ) );
	}

	public function test_core_pages_are_excluded_from_seo_plugin_sitemaps() {
		$expected = array_values( $this->pages );

		foreach ( array( 'wpseo_exclude_from_sitemap_by_post_ids', 'rank_math/sitemap/posts_to_exclude' ) as $filter ) {
			$excluded = apply_filters( $filter, array( 42 ) );

			$this->assertContains( 42, $excluded, $filter );
			foreach ( $expected as $page_id ) {
				$this->assertContains( $page_id, $excluded, $filter );
			}
		}
	}
}
