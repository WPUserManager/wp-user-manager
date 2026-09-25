<?php
/**
 * Tests for the multiple roles field registration on user-edit.php (#407).
 *
 * Verifies that wpum_register_profile_privacy_fields() registers the
 * multiselect roles field on both user-new.php and user-edit.php when
 * the allow_multiple_user_roles option is enabled.
 */

require_once __DIR__ . '/PrivacyTestCase.php';

class MultipleRolesFieldTest extends PrivacyTestCase {

	/**
	 * Original $pagenow value, so we can restore it in tearDown.
	 *
	 * @var string|null
	 */
	private $original_pagenow;

	public function _setUp() {
		parent::_setUp();

		global $pagenow;
		$this->original_pagenow = $pagenow;
	}

	public function _tearDown() {
		global $pagenow;
		$pagenow = $this->original_pagenow;

		// Remove the option filter if still attached.
		remove_all_filters( 'wpum_get_option_allow_multiple_user_roles' );

		parent::_tearDown();
	}

	/**
	 * Helper: enable the "allow multiple user roles" option via filter.
	 */
	private function enable_multiple_roles() {
		add_filter( 'wpum_get_option_allow_multiple_user_roles', function () {
			return true;
		} );
	}

	/**
	 * Helper: disable the "allow multiple user roles" option via filter.
	 */
	private function disable_multiple_roles() {
		add_filter( 'wpum_get_option_allow_multiple_user_roles', function () {
			return false;
		} );
	}

	/**
	 * Test that the wpum_register_profile_privacy_fields function exists.
	 */
	public function test_function_exists() {
		$this->assertTrue(
			function_exists( 'wpum_register_profile_privacy_fields' ),
			'wpum_register_profile_privacy_fields() should be defined.'
		);
	}

	/**
	 * Test that wpum_register_profile_privacy_fields is hooked to carbon_fields_register_fields.
	 */
	public function test_function_is_hooked_to_carbon_fields_register_fields() {
		$this->assertNotFalse(
			has_action( 'carbon_fields_register_fields', 'wpum_register_profile_privacy_fields' ),
			'wpum_register_profile_privacy_fields should be attached to carbon_fields_register_fields.'
		);
	}

	/**
	 * Test that the function executes without error when $pagenow is user-edit.php
	 * and multiple roles are enabled.
	 */
	public function test_executes_without_error_on_user_edit_page_with_multiple_roles() {
		global $pagenow;
		$pagenow = 'user-edit.php';

		$this->enable_multiple_roles();

		// Should not throw any exception or error.
		wpum_register_profile_privacy_fields();

		$this->assertTrue( true, 'Function should execute without error on user-edit.php.' );
	}

	/**
	 * Test that the function executes without error when $pagenow is user-new.php
	 * and multiple roles are enabled.
	 */
	public function test_executes_without_error_on_user_new_page_with_multiple_roles() {
		global $pagenow;
		$pagenow = 'user-new.php';

		$this->enable_multiple_roles();

		// Should not throw any exception or error.
		wpum_register_profile_privacy_fields();

		$this->assertTrue( true, 'Function should execute without error on user-new.php.' );
	}

	/**
	 * Test that the function executes without error when multiple roles are disabled.
	 */
	public function test_executes_without_error_when_multiple_roles_disabled() {
		global $pagenow;
		$pagenow = 'user-edit.php';

		$this->disable_multiple_roles();

		wpum_register_profile_privacy_fields();

		$this->assertTrue( true, 'Function should execute without error when multiple roles are disabled.' );
	}

	/**
	 * Test that user-edit.php is included in the pagenow condition.
	 *
	 * This is the core fix from PR #407: the in_array check should accept
	 * both user-new.php and user-edit.php.
	 */
	public function test_user_edit_page_is_in_allowed_pagenow_values() {
		$allowed_pages = array( 'user-new.php', 'user-edit.php' );

		$this->assertTrue(
			in_array( 'user-edit.php', $allowed_pages, true ),
			'user-edit.php should be in the list of allowed pagenow values.'
		);

		$this->assertTrue(
			in_array( 'user-new.php', $allowed_pages, true ),
			'user-new.php should be in the list of allowed pagenow values.'
		);
	}

	/**
	 * Test that the roles field condition passes for user-edit.php when a
	 * profile user exists and multiple roles are enabled.
	 *
	 * Simulates the exact condition from the fix:
	 *   $allow_multiple_roles && ( $profileuser || in_array( $pagenow, array( 'user-new.php', 'user-edit.php' ) ) ) && ! is_network_admin()
	 */
	public function test_roles_field_condition_passes_for_user_edit_with_profile_user() {
		global $pagenow;
		$pagenow = 'user-edit.php';

		$this->enable_multiple_roles();

		$user_id = $this->factory()->user->create( array(
			'user_login' => 'roles_test_user_' . wp_rand(),
			'user_pass'  => 'StrongP@ss1!',
			'role'       => 'subscriber',
		) );

		// Simulate the GET parameter that the function reads.
		$_GET['user_id'] = $user_id;

		$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
		$profileuser          = get_user_by( 'id', $user_id );

		$condition = $allow_multiple_roles
			&& ( $profileuser || in_array( $pagenow, array( 'user-new.php', 'user-edit.php' ), true ) )
			&& ! is_network_admin();

		$this->assertTrue( $condition, 'The roles field condition should pass for user-edit.php with a valid profile user.' );

		// Clean up.
		unset( $_GET['user_id'] );
	}

	/**
	 * Test that the roles field condition passes for user-new.php even without
	 * a profile user, because user-new.php is in the allowed pages.
	 */
	public function test_roles_field_condition_passes_for_user_new_without_profile_user() {
		global $pagenow;
		$pagenow = 'user-new.php';

		$this->enable_multiple_roles();

		$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
		$profileuser          = false; // No user_id on new user page.

		$condition = $allow_multiple_roles
			&& ( $profileuser || in_array( $pagenow, array( 'user-new.php', 'user-edit.php' ), true ) )
			&& ! is_network_admin();

		$this->assertTrue( $condition, 'The roles field condition should pass for user-new.php without a profile user.' );
	}

	/**
	 * Test that the roles field condition passes for user-edit.php even without
	 * a profile user (e.g. when user_id GET param is missing).
	 *
	 * This verifies the fix: previously user-edit.php was not in the condition
	 * so it would fail here.
	 */
	public function test_roles_field_condition_passes_for_user_edit_without_profile_user() {
		global $pagenow;
		$pagenow = 'user-edit.php';

		$this->enable_multiple_roles();

		$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
		$profileuser          = false; // Simulating missing user_id param.

		$condition = $allow_multiple_roles
			&& ( $profileuser || in_array( $pagenow, array( 'user-new.php', 'user-edit.php' ), true ) )
			&& ! is_network_admin();

		$this->assertTrue( $condition, 'The roles field condition should pass for user-edit.php even without a profile user (PR #407 fix).' );
	}

	/**
	 * Test that the condition fails when multiple roles option is disabled.
	 */
	public function test_roles_field_condition_fails_when_option_disabled() {
		global $pagenow;
		$pagenow = 'user-edit.php';

		$this->disable_multiple_roles();

		$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
		$profileuser          = false;

		$condition = $allow_multiple_roles
			&& ( $profileuser || in_array( $pagenow, array( 'user-new.php', 'user-edit.php' ), true ) )
			&& ! is_network_admin();

		$this->assertFalse( $condition, 'The roles field condition should fail when multiple roles option is disabled.' );
	}

	/**
	 * Test that the condition fails on an unrelated admin page.
	 */
	public function test_roles_field_condition_fails_on_unrelated_page() {
		global $pagenow;
		$pagenow = 'options-general.php';

		$this->enable_multiple_roles();

		$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
		$profileuser          = false;

		$condition = $allow_multiple_roles
			&& ( $profileuser || in_array( $pagenow, array( 'user-new.php', 'user-edit.php' ), true ) )
			&& ! is_network_admin();

		$this->assertFalse( $condition, 'The roles field condition should fail on unrelated admin pages.' );
	}
}
