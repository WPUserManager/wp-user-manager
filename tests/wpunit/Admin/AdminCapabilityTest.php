<?php

/**
 * Test the shared check for who can manage WP User Manager.
 *
 * Every admin screen and ajax handler checks the capability from the
 * `wpum_admin_pages_capability` filter. These helpers are the one place that
 * reads it.
 */
class AdminCapabilityTest extends WPUMTestCase {

	public function _tearDown() {
		remove_all_filters( 'wpum_admin_pages_capability' );
		wp_set_current_user( 0 );

		parent::_tearDown();
	}

	public function test_default_capability_is_manage_options() {
		$this->assertSame( 'manage_options', wpum_get_admin_capability() );
	}

	public function test_capability_can_be_filtered() {
		add_filter( 'wpum_admin_pages_capability', function () {
			return 'edit_pages';
		} );

		$this->assertSame( 'edit_pages', wpum_get_admin_capability() );
	}

	public function test_administrator_can_manage() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertTrue( wpum_current_user_can_manage() );
	}

	public function test_editor_cannot_manage_by_default() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertFalse( wpum_current_user_can_manage() );
	}

	public function test_subscriber_cannot_manage() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertFalse( wpum_current_user_can_manage() );
	}

	public function test_logged_out_visitor_cannot_manage() {
		wp_set_current_user( 0 );

		$this->assertFalse( wpum_current_user_can_manage() );
	}

	public function test_filtered_capability_lets_editor_manage() {
		add_filter( 'wpum_admin_pages_capability', function () {
			return 'edit_pages';
		} );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );
		$this->assertTrue( wpum_current_user_can_manage(), 'An editor has edit_pages, so the filtered check should pass.' );

		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertFalse( wpum_current_user_can_manage(), 'A subscriber does not have edit_pages.' );
	}

	/**
	 * The filter is read when the check runs, so a filter added after the
	 * plugin loads still applies.
	 */
	public function test_filter_added_late_still_applies() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertTrue( wpum_current_user_can_manage() );

		add_filter( 'wpum_admin_pages_capability', function () {
			return 'wpum_capability_nobody_has';
		} );

		$this->assertFalse( wpum_current_user_can_manage() );
	}
}
