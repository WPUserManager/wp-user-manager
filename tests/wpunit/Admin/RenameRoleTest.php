<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Renaming a role changes its display name only, never its slug.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/381
 */
class RenameRoleTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		// The roles code is only loaded in wp-admin.
		if ( ! function_exists( 'wpum_rename_role' ) ) {
			foreach ( array( 'class-wpum-role', 'class-wpum-capability', 'class-wpum-capability-group', 'class-wpum-collection', 'class-wpum-roles', 'functions' ) as $file ) {
				require_once WPUM_PLUGIN_DIR . 'includes/roles/' . $file . '.php';
			}
		}

		add_role( 'wpum_test_role', 'Test Role', array( 'read' => true ) );
	}

	public function _tearDown() {
		remove_role( 'wpum_test_role' );
		wp_roles()->for_site();
		parent::_tearDown();
	}

	public function test_renames_a_custom_role_and_keeps_its_slug() {
		$user_id = $this->factory()->user->create( array( 'role' => 'wpum_test_role' ) );

		$this->assertTrue( wpum_rename_role( 'wpum_test_role', 'Member' ) );

		$stored = get_option( wp_roles()->role_key );
		$this->assertSame( 'Member', $stored['wpum_test_role']['name'] );
		$this->assertSame( 'Member', wp_roles()->role_names['wpum_test_role'] );
		$this->assertSame( array( 'read' => true ), $stored['wpum_test_role']['capabilities'] );

		$user = get_userdata( $user_id );
		$this->assertContains( 'wpum_test_role', $user->roles );
		$this->assertTrue( user_can( $user, 'read' ) );
	}

	public function test_renames_a_core_role_display_name_only() {
		$this->assertTrue( wpum_rename_role( 'subscriber', 'Member' ) );

		$this->assertSame( 'Member', wp_roles()->roles['subscriber']['name'] );
		$this->assertNotNull( get_role( 'subscriber' ) );

		wpum_rename_role( 'subscriber', 'Subscriber' );
	}

	public function test_updates_the_wpum_role_label() {
		wpum_register_role( 'wpum_test_role', array( 'label' => 'Test Role' ) );

		wpum_rename_role( 'wpum_test_role', 'Member' );

		$this->assertSame( 'Member', wpum_get_role( 'wpum_test_role' )->label );
	}

	public function test_rejects_unknown_role() {
		$result = wpum_rename_role( 'no_such_role', 'Member' );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_role_not_found', $result->get_error_code() );
	}

	public function test_rejects_empty_name() {
		$result = wpum_rename_role( 'wpum_test_role', '   ' );

		$this->assertWPError( $result );
		$this->assertSame( 'Test Role', wp_roles()->roles['wpum_test_role']['name'] );
	}

	public function test_rejects_name_used_by_another_role() {
		$result = wpum_rename_role( 'wpum_test_role', 'editor' );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_role_name_exists', $result->get_error_code() );
		$this->assertSame( 'Test Role', wp_roles()->roles['wpum_test_role']['name'] );
	}

	public function test_allows_changing_case_of_own_name() {
		$this->assertTrue( wpum_rename_role( 'wpum_test_role', 'test role' ) );
		$this->assertSame( 'test role', wp_roles()->roles['wpum_test_role']['name'] );
	}
}
