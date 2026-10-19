<?php

/**
 * Test assigning pages to the WPUM core pages.
 */
class CorePageAssignmentTest extends WPUMTestCase {

	/**
	 * @var array|false
	 */
	protected $original_settings;

	public function _setUp() {
		parent::_setUp();

		global $wpum_options;
		$this->original_settings = $wpum_options;
	}

	public function _tearDown() {
		global $wpum_options;
		$wpum_options = $this->original_settings;
		update_option( 'wpum_settings', $this->original_settings );

		parent::_tearDown();
	}

	/**
	 * @param string $status
	 * @param string $type
	 *
	 * @return int
	 */
	protected function create_page( $status = 'publish', $type = 'page' ) {
		return $this->factory()->post->create( array(
			'post_type'   => $type,
			'post_status' => $status,
		) );
	}

	public function test_assign_page() {
		$page_id = $this->create_page();

		$this->assertTrue( wpum_assign_core_page( 'login', $page_id ) );
		$this->assertSame( $page_id, wpum_get_core_page_id( 'login' ) );
		$this->assertSame( array( $page_id ), wpum_get_option( 'login_page' ) );
	}

	public function test_every_core_page_can_be_assigned_and_read_back() {
		foreach ( array_keys( wpum_get_core_page_options() ) as $page ) {
			$page_id = $this->create_page();

			$this->assertTrue( wpum_assign_core_page( $page, $page_id ), $page );
			$this->assertSame( $page_id, wpum_get_core_page_id( $page ), $page );
		}
	}

	public function test_page_options_match_the_settings_keys() {
		$this->assertSame( 'registration_page', wpum_get_core_page_options()['register'] );
		$this->assertSame( 'password_recovery_page', wpum_get_core_page_options()['password'] );
	}

	public function test_private_page_is_allowed() {
		$page_id = $this->create_page( 'private' );

		$this->assertTrue( wpum_assign_core_page( 'account', $page_id ) );
	}

	public function test_unassign_with_zero() {
		wpum_assign_core_page( 'profile', $this->create_page() );

		$this->assertTrue( wpum_assign_core_page( 'profile', 0 ) );
		$this->assertFalse( wpum_get_core_page_id( 'profile' ) );
	}

	public function test_assigning_the_same_page_again_succeeds() {
		$page_id = $this->create_page();

		wpum_assign_core_page( 'login', $page_id );

		$this->assertTrue( wpum_assign_core_page( 'login', $page_id ) );
		$this->assertTrue( wpum_assign_core_page( 'profile', 0 ) );
		$this->assertTrue( wpum_assign_core_page( 'profile', 0 ), 'Unassigning twice is still a success.' );
	}

	public function test_failed_save_returns_error() {
		$old_page = $this->create_page();
		wpum_assign_core_page( 'login', $old_page );

		// Block the write.
		$block = function ( $value, $old_value ) {
			return $old_value;
		};
		add_filter( 'pre_update_option_wpum_settings', $block, 10, 2 );

		$result = wpum_assign_core_page( 'login', $this->create_page() );

		remove_filter( 'pre_update_option_wpum_settings', $block, 10 );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_core_page_not_saved', $result->get_error_code() );
		$this->assertSame( $old_page, wpum_get_core_page_id( 'login' ) );
	}

	public function test_unknown_core_page_is_rejected() {
		$result = wpum_assign_core_page( 'members', $this->create_page() );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_unknown_core_page', $result->get_error_code() );
	}

	/**
	 * @dataProvider invalid_pages
	 *
	 * @param string $status
	 * @param string $type
	 */
	public function test_invalid_page_is_rejected( $status, $type ) {
		wpum_assign_core_page( 'login', $this->create_page() );
		$assigned = wpum_get_core_page_id( 'login' );

		$result = wpum_assign_core_page( 'login', $this->create_page( $status, $type ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_invalid_core_page', $result->get_error_code() );
		$this->assertSame( $assigned, wpum_get_core_page_id( 'login' ), 'The assigned page should not change.' );
	}

	/**
	 * @return array
	 */
	public function invalid_pages() {
		return array(
			'draft page' => array( 'draft', 'page' ),
			'trash page' => array( 'trash', 'page' ),
			'post'       => array( 'publish', 'post' ),
		);
	}

	public function test_missing_page_is_rejected() {
		$result = wpum_assign_core_page( 'login', 999999 );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_invalid_core_page', $result->get_error_code() );
	}

	public function test_unknown_page_key_reads_as_false() {
		$this->assertFalse( wpum_get_core_page_id( 'members' ) );
		$this->assertNull( wpum_get_core_page_id() );
	}
}
