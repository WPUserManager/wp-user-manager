<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Test the multiple user roles UI on the admin user edit page.
 *
 * Carbon Fields 3.x renders fields via React. The UI function uses a
 * MutationObserver to relocate the entire CF container (preserving the
 * React root) next to the WP role dropdown, then hides the WP dropdown.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/431
 */
class MultipleRolesUiTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();
		wpum_update_option( 'allow_multiple_user_roles', true );
	}

	public function _tearDown() {
		wpum_update_option( 'allow_multiple_user_roles', false );
		parent::_tearDown();
	}

	/**
	 * The UI script should use a MutationObserver to wait for the CF React
	 * field (.wpum-multiple-user-roles) and then relocate the container.
	 */
	public function test_roles_ui_script_uses_mutation_observer() {
		$user = $this->factory()->user->create_and_get( array( 'role' => 'subscriber' ) );

		ob_start();
		wpum_modify_multiple_roles_ui( $user );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'MutationObserver', $output, 'Script should use MutationObserver to detect CF rendering.' );
		$this->assertStringContainsString( '.wpum-multiple-user-roles', $output, 'Script should target the .wpum-multiple-user-roles class.' );
	}

	/**
	 * The UI script should hide the default WordPress role dropdown.
	 */
	public function test_roles_ui_hides_wp_default_role_dropdown() {
		$user = $this->factory()->user->create_and_get( array( 'role' => 'subscriber' ) );

		ob_start();
		wpum_modify_multiple_roles_ui( $user );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'user-role-wrap', $output, 'Script should reference .user-role-wrap to hide the WP role dropdown.' );
		$this->assertStringContainsString( '.hide()', $output, 'Script should use jQuery .hide() to hide the WP role dropdown.' );
	}

	/**
	 * When multiple roles is disabled, the UI function should output nothing.
	 */
	public function test_roles_ui_outputs_nothing_when_disabled() {
		wpum_update_option( 'allow_multiple_user_roles', false );

		$user = $this->factory()->user->create_and_get( array( 'role' => 'subscriber' ) );

		ob_start();
		wpum_modify_multiple_roles_ui( $user );
		$output = ob_get_clean();

		$this->assertEmpty( trim( $output ), 'No output expected when multiple roles is disabled.' );
	}

	/**
	 * The multiple roles field registration function should exist.
	 */
	public function test_multiselect_field_registered_when_multiple_roles_enabled() {
		$this->assertTrue(
			function_exists( 'wpum_register_multiple_roles_field' ),
			'wpum_register_multiple_roles_field() should be defined.'
		);

		$this->assertTrue(
			(bool) wpum_get_option( 'allow_multiple_user_roles' ),
			'Multiple roles option should be enabled for this test.'
		);
	}
}
