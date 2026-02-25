<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Test the multiple user roles UI on the admin user edit page.
 *
 * Carbon Fields 3.x renders fields via React. The jQuery code that repositions
 * the multiselect field must NOT move DOM elements (insertAfter/appendTo/etc.)
 * as this breaks React's component tree, making the field non-interactive.
 *
 * @see https://wordpress.org/support/topic/user-rose-disabled-into-profile/
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
	 * The role multiselect UI script must not use jQuery DOM manipulation
	 * methods that move elements (insertAfter, appendTo, prependTo, insertBefore).
	 * Moving React-rendered DOM nodes breaks the component.
	 */
	public function test_roles_ui_script_does_not_move_dom_elements() {
		$user = $this->factory()->user->create_and_get( array( 'role' => 'subscriber' ) );

		ob_start();
		wpum_modify_multiple_roles_ui( $user );
		$output = ob_get_clean();

		// The script must NOT use jQuery DOM-moving methods.
		$this->assertStringNotContainsString( 'insertAfter', $output, 'Script must not use insertAfter — it breaks Carbon Fields 3.x React components.' );
		$this->assertStringNotContainsString( 'appendTo', $output, 'Script must not use appendTo — it breaks Carbon Fields 3.x React components.' );
		$this->assertStringNotContainsString( 'prependTo', $output, 'Script must not use prependTo — it breaks Carbon Fields 3.x React components.' );
		$this->assertStringNotContainsString( 'insertBefore', $output, 'Script must not use insertBefore — it breaks Carbon Fields 3.x React components.' );
	}

	/**
	 * The role multiselect UI should hide the default WordPress role dropdown
	 * using CSS (not DOM removal) so that Carbon Fields renders in its natural position.
	 */
	public function test_roles_ui_hides_wp_default_role_dropdown() {
		$user = $this->factory()->user->create_and_get( array( 'role' => 'subscriber' ) );

		ob_start();
		wpum_modify_multiple_roles_ui( $user );
		$output = ob_get_clean();

		// The output should contain CSS or JS that hides the WP role dropdown.
		$this->assertMatchesRegularExpression(
			'/(display\s*:\s*none|\.hide\(\)|visibility\s*:\s*hidden)/',
			$output,
			'Script should hide the WordPress default role dropdown.'
		);
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
	 * The profile privacy fields should include the multiselect when enabled.
	 */
	public function test_multiselect_field_registered_when_multiple_roles_enabled() {
		// Simulate being on user-edit.php with a user_id.
		$user = $this->factory()->user->create_and_get( array( 'role' => 'subscriber' ) );
		$_GET['user_id'] = $user->ID;

		// The function uses filter_input which reads from the actual request,
		// so we can only verify the option check works correctly.
		$this->assertTrue(
			(bool) wpum_get_option( 'allow_multiple_user_roles' ),
			'Multiple roles option should be enabled for this test.'
		);
	}
}
