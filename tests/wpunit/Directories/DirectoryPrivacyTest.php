<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Directories keep email addresses out of search, sorting and profile links.
 */
class DirectoryPrivacyTest extends WPUMTestCase {

	/**
	 * @var array Original option values, restored after each test.
	 */
	private $original = array();

	public function _setUp() {
		parent::_setUp();

		foreach ( array( 'profile_page', 'obfuscate_display_name_emails' ) as $option ) {
			$this->original[ $option ] = wpum_get_option( $option );
		}

		$profile_page = $this->factory()->post->create( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
		) );

		wpum_update_option( 'profile_page', array( $profile_page ) );
		wpum_update_option( 'obfuscate_display_name_emails', false );
		update_option( 'wpum_permalink', 'username' );
		wp_set_current_user( 0 );
	}

	public function _tearDown() {
		foreach ( $this->original as $option => $value ) {
			wpum_update_option( $option, $value );
		}

		set_query_var( 'profile', '' );
		delete_transient( 'wpum_has_email_display_names' );
		remove_all_filters( 'wpum_directory_search_columns' );
		remove_all_filters( 'wpum_get_directory_sort_by_methods' );
		parent::_tearDown();
	}

	/**
	 * Run the user query a directory search runs.
	 *
	 * @param string $term
	 *
	 * @return int[] Matching user IDs.
	 */
	private function search( $term ) {
		$exclude_masked = function ( $query ) {
			$query->query_where = wpum_exclude_masked_display_names_from_search( $query->query_where );
		};

		add_action( 'pre_user_query', $exclude_masked );

		$query = new WP_User_Query( array(
			'search'         => '*' . $term . '*',
			'search_columns' => wpum_get_directory_search_columns( 1 ),
			'fields'         => 'ID',
		) );

		remove_action( 'pre_user_query', $exclude_masked );

		return array_map( 'intval', $query->get_results() );
	}

	/**
	 * Create a member who registered with only an email address.
	 *
	 * @param string $email
	 *
	 * @return int
	 */
	private function create_email_member( $email = 'sam@hidden.example' ) {
		return $this->factory()->user->create( array(
			'user_login'   => $email,
			'user_email'   => $email,
			'display_name' => $email,
		) );
	}

	public function test_search_finds_a_display_name() {
		$user_id = $this->factory()->user->create( array( 'display_name' => 'Jo Bloggs' ) );

		$this->assertSame( array( $user_id ), $this->search( 'Bloggs' ) );
	}

	public function search_terms_for_private_columns() {
		return array(
			'email domain'  => array( '@acme.example' ),
			'full email'    => array( 'jo@acme.example' ),
			'domain, no @'  => array( 'acme' ),
			'login'         => array( 'jlogin77' ),
			'website'       => array( 'https://jo-site.example' ),
		);
	}

	/**
	 * @dataProvider search_terms_for_private_columns
	 */
	public function test_guest_search_skips_private_columns( $term ) {
		$this->factory()->user->create( array(
			'user_login'   => 'jlogin77',
			'user_email'   => 'jo@acme.example',
			'user_url'     => 'https://jo-site.example',
			'display_name' => 'Jo Bloggs',
		) );

		$this->assertSame( array(), $this->search( $term ) );
	}

	public function test_guest_search_skips_the_user_id() {
		$user_id = $this->factory()->user->create( array( 'display_name' => 'Jo Bloggs' ) );

		$this->assertNotContains( $user_id, $this->search( (string) $user_id ) );
	}

	public function test_subscriber_search_skips_private_columns() {
		$this->factory()->user->create( array(
			'user_email'   => 'jo@acme.example',
			'display_name' => 'Jo Bloggs',
		) );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( array(), $this->search( '@acme.example' ) );
	}

	public function test_a_user_who_can_list_users_can_search_by_email() {
		$user_id = $this->factory()->user->create( array( 'user_email' => 'jo@acme.example' ) );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertSame( array( $user_id ), $this->search( '@acme.example' ) );
	}

	public function test_a_user_who_can_list_users_can_search_by_id() {
		$user_id = $this->factory()->user->create();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertContains( $user_id, $this->search( (string) $user_id ) );
	}

	public function test_search_columns_can_be_filtered() {
		$user_id = $this->factory()->user->create( array( 'user_login' => 'jlogin77' ) );

		add_filter( 'wpum_directory_search_columns', function () {
			return array( 'user_login' );
		} );

		$this->assertSame( array( $user_id ), $this->search( 'jlogin77' ) );
	}

	public function test_empty_search_columns_do_not_fall_back_to_core_defaults() {
		add_filter( 'wpum_directory_search_columns', '__return_empty_array' );

		$this->assertSame( array( 'display_name' ), wpum_get_directory_search_columns( 1 ) );
	}

	public function test_masked_display_names_do_not_match_on_the_address() {
		$this->create_email_member();
		wpum_update_option( 'obfuscate_display_name_emails', true );

		$this->assertSame( array(), $this->search( '@hidden.example' ) );
		$this->assertSame( array(), $this->search( 'hidden' ) );
	}

	public function test_masking_leaves_other_display_names_searchable() {
		$user_id = $this->factory()->user->create( array( 'display_name' => 'Jo Bloggs' ) );
		wpum_update_option( 'obfuscate_display_name_emails', true );

		$this->assertSame( array( $user_id ), $this->search( 'Bloggs' ) );
	}

	public function names_with_an_at_sign_that_are_not_emails() {
		return array(
			'spaces'         => array( 'Jo @ Bloggs' ),
			'no dot'         => array( 'Jo@Bloggs' ),
			'invalid domain' => array( 'Jo@Bloggs.example/team' ),
		);
	}

	/**
	 * @dataProvider names_with_an_at_sign_that_are_not_emails
	 */
	public function test_masking_leaves_a_name_with_an_at_sign_searchable( $display_name ) {
		$user_id = $this->factory()->user->create( array( 'display_name' => $display_name ) );
		wpum_update_option( 'obfuscate_display_name_emails', true );

		$this->assertSame( array( $user_id ), $this->search( 'Bloggs' ) );
	}

	public function test_where_clause_is_untouched_without_masking() {
		$where = "WHERE (display_name LIKE '%x%')";

		$this->assertSame( $where, wpum_exclude_masked_display_names_from_search( $where ) );
	}

	public function test_sort_method_accepts_a_listed_method() {
		$this->assertSame( 'oldest', wpum_get_directory_sort_method( 'oldest', 'newest' ) );
	}

	public function test_sort_method_accepts_one_added_by_filter() {
		add_filter( 'wpum_get_directory_sort_by_methods', function ( $methods ) {
			$methods['company'] = 'Company';

			return $methods;
		} );

		$this->assertSame( 'company', wpum_get_directory_sort_method( 'company', 'newest' ) );
	}

	public function test_sort_method_rejects_any_other_meta_key() {
		$this->assertSame( 'newest', wpum_get_directory_sort_method( 'wp_capabilities', 'newest' ) );
		$this->assertSame( 'newest', wpum_get_directory_sort_method( '', 'newest' ) );
	}

	public function test_email_name_is_masked_for_guests() {
		wpum_update_option( 'obfuscate_display_name_emails', true );
		$user_id = $this->create_email_member();

		$masked = wpum_mask_public_email( 'sam@hidden.example', $user_id );

		$this->assertStringNotContainsString( 'hidden', $masked );
	}

	public function test_email_name_is_not_masked_without_the_setting() {
		$user_id = $this->create_email_member();

		$this->assertSame( 'sam@hidden.example', wpum_mask_public_email( 'sam@hidden.example', $user_id ) );
	}

	public function test_other_names_are_not_masked() {
		wpum_update_option( 'obfuscate_display_name_emails', true );

		$this->assertSame( 'Jo Bloggs', wpum_mask_public_email( 'Jo Bloggs', 1 ) );
	}

	public function test_users_see_their_own_email_name() {
		wpum_update_option( 'obfuscate_display_name_emails', true );
		$user_id = $this->create_email_member();
		wp_set_current_user( $user_id );

		$this->assertSame( 'sam@hidden.example', wpum_mask_public_email( 'sam@hidden.example', (string) $user_id ) );
	}

	public function test_users_who_can_list_users_see_email_names() {
		wpum_update_option( 'obfuscate_display_name_emails', true );
		$user_id = $this->create_email_member();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertSame( 'sam@hidden.example', wpum_mask_public_email( 'sam@hidden.example', $user_id ) );
	}

	public function test_other_members_do_not_see_email_names() {
		wpum_update_option( 'obfuscate_display_name_emails', true );
		$user_id = $this->create_email_member();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertNotSame( 'sam@hidden.example', wpum_mask_public_email( 'sam@hidden.example', $user_id ) );
	}

	public function test_display_name_filter_masks_an_email() {
		wpum_update_option( 'obfuscate_display_name_emails', true );
		$user = get_user_by( 'id', $this->create_email_member() );

		$masked = apply_filters( 'wpum_user_display_name', $user->display_name, $user );

		$this->assertStringNotContainsString( 'hidden', $masked );
	}

	public function test_has_email_display_names_finds_one() {
		$this->create_email_member();

		$this->assertTrue( wpum_has_email_display_names() );
	}

	public function test_has_email_display_names_uses_its_cached_answer() {
		set_transient( 'wpum_has_email_display_names', 0, DAY_IN_SECONDS );
		$this->create_email_member();

		$this->assertFalse( wpum_has_email_display_names() );
	}

	public function test_email_name_regexp_matches_an_address() {
		global $wpdb;

		$this->assertSame( '1', $wpdb->get_var( $wpdb->prepare( 'SELECT %s REGEXP %s', 'sam@hidden.example', wpum_get_email_name_regexp() ) ) );
	}

	/**
	 * @dataProvider names_with_an_at_sign_that_are_not_emails
	 */
	public function test_email_name_regexp_skips_other_names( $display_name ) {
		global $wpdb;

		$this->assertSame( '0', $wpdb->get_var( $wpdb->prepare( 'SELECT %s REGEXP %s', $display_name, wpum_get_email_name_regexp() ) ) );
	}

	public function test_fresh_install_masks_email_names() {
		$version = get_option( 'wpum_version' );
		delete_option( 'wpum_version' );

		wpum_run_install();

		$this->assertTrue( (bool) wpum_get_option( 'obfuscate_display_name_emails' ) );
		update_option( 'wpum_version', $version );
	}

	public function test_reactivation_does_not_turn_on_masking() {
		$version = get_option( 'wpum_version' );
		update_option( 'wpum_version', '2.9.21' );

		wpum_run_install();

		$this->assertFalse( (bool) wpum_get_option( 'obfuscate_display_name_emails' ) );
		update_option( 'wpum_version', $version );
	}

	public function test_profile_url_replaces_an_email_login_with_the_id() {
		$user_id = $this->create_email_member();
		$url     = wpum_get_profile_url( get_user_by( 'id', $user_id ) );

		$this->assertStringEndsWith( '/' . $user_id, $url );
		$this->assertStringNotContainsString( 'hidden', $url );
	}

	public function test_profile_url_keeps_a_normal_login() {
		$user_id = $this->factory()->user->create( array( 'user_login' => 'jlogin77' ) );

		$this->assertStringEndsWith( '/jlogin77', wpum_get_profile_url( get_user_by( 'id', $user_id ) ) );
	}

	public function test_profile_url_replaces_an_email_nickname_with_the_id() {
		update_option( 'wpum_permalink', 'nickname' );
		$user_id = $this->create_email_member();
		update_user_meta( $user_id, 'nickname', 'sam@hidden.example' );

		$this->assertStringEndsWith( '/' . $user_id, wpum_get_profile_url( get_user_by( 'id', $user_id ) ) );
	}

	public function test_id_in_profile_url_finds_a_user_with_an_email_login() {
		$user_id = $this->create_email_member();

		set_query_var( 'profile', (string) $user_id );

		$this->assertSame( $user_id, wpum_get_queried_user_id() );
	}

	public function test_id_in_profile_url_finds_a_user_with_an_email_nickname() {
		update_option( 'wpum_permalink', 'nickname' );
		$user_id = $this->create_email_member();
		update_user_meta( $user_id, 'nickname', 'sam@hidden.example' );

		set_query_var( 'profile', (string) $user_id );

		$this->assertSame( $user_id, wpum_get_queried_user_id() );
	}

	public function test_old_profile_url_with_an_email_login_still_works() {
		$user_id = $this->create_email_member();

		set_query_var( 'profile', rawurlencode( 'sam@hidden.example' ) );

		$this->assertSame( $user_id, wpum_get_queried_user_id() );
	}

	public function test_id_in_profile_url_does_not_find_a_user_with_a_normal_login() {
		$user_id = $this->factory()->user->create( array( 'user_login' => 'jlogin77' ) );

		set_query_var( 'profile', (string) $user_id );

		$this->assertFalse( wpum_get_queried_user_id() );
	}

	public function test_numeric_login_wins_over_a_matching_id() {
		$email_member = $this->create_email_member();
		$numeric      = $this->factory()->user->create( array( 'user_login' => (string) $email_member ) );

		set_query_var( 'profile', (string) $email_member );

		$this->assertSame( $numeric, wpum_get_queried_user_id() );
	}
}
