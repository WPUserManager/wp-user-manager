<?php
/**
 * Tests for content restriction shortcodes.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ContentRestrictionTest extends WPUMTestCase {

	public function _tearDown() {
		wp_set_current_user( 0 );
		parent::_tearDown();
	}

	/**
	 * Test that [wpum_restrict_logged_in] shows content when user is logged in.
	 */
	public function test_restrict_logged_in_shows_content_when_logged_in() {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$output = do_shortcode( '[wpum_restrict_logged_in]Secret Content[/wpum_restrict_logged_in]' );

		$this->assertStringContainsString( 'Secret Content', $output, 'Content should be visible to logged-in users.' );
	}

	/**
	 * Test that [wpum_restrict_logged_in] hides content when user is logged out.
	 */
	public function test_restrict_logged_in_hides_content_when_logged_out() {
		wp_set_current_user( 0 );

		$output = do_shortcode( '[wpum_restrict_logged_in]Secret Content[/wpum_restrict_logged_in]' );

		$this->assertStringNotContainsString( 'Secret Content', $output, 'Content should be hidden from logged-out users.' );
	}

	/**
	 * Test that [wpum_restrict_logged_out] shows content when user is logged out.
	 */
	public function test_restrict_logged_out_shows_content_when_logged_out() {
		wp_set_current_user( 0 );

		$output = do_shortcode( '[wpum_restrict_logged_out]Guest Content[/wpum_restrict_logged_out]' );

		$this->assertStringContainsString( 'Guest Content', $output, 'Content should be visible to logged-out users.' );
	}

	/**
	 * Test that [wpum_restrict_logged_out] returns empty when user is logged in.
	 * The shortcode returns '' early when user is logged in and show_message is 'no'.
	 * Note: The shortcode starts ob_start() but returns '' before ob_get_clean(),
	 * leaving an orphaned output buffer. We clean it up.
	 */
	public function test_restrict_logged_out_hides_content_when_logged_in() {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$ob_level_before = ob_get_level();
		$output = do_shortcode( '[wpum_restrict_logged_out show_message="no"]Logged Out Content[/wpum_restrict_logged_out]' );
		// Clean up any orphaned output buffers from the shortcode's early return.
		while ( ob_get_level() > $ob_level_before ) {
			ob_end_clean();
		}

		$this->assertEmpty( $output, 'Content should be empty for logged-in users with show_message=no.' );
	}

	/**
	 * Test that [wpum_restrict_to_users] shows content for a matching user.
	 */
	public function test_restrict_to_users_shows_for_matching_user() {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$output = do_shortcode( '[wpum_restrict_to_users ids="' . $user_id . '"]User Specific Content[/wpum_restrict_to_users]' );

		$this->assertStringContainsString( 'User Specific Content', $output, 'Content should be visible to matching user.' );
	}

	/**
	 * Test that [wpum_restrict_to_users] returns empty for a non-matching user
	 * when show_message is 'no'.
	 */
	public function test_restrict_to_users_hides_for_non_matching_user() {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$other_user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $other_user_id );

		$ob_level_before = ob_get_level();
		$output = do_shortcode( '[wpum_restrict_to_users ids="' . $user_id . '" show_message="no"]User Specific Content[/wpum_restrict_to_users]' );
		while ( ob_get_level() > $ob_level_before ) {
			ob_end_clean();
		}

		$this->assertEmpty( $output, 'Content should be empty for non-matching user with show_message=no.' );
	}

	/**
	 * Test that [wpum_restrict_to_user_roles] shows content for a matching role.
	 */
	public function test_restrict_to_roles_shows_for_matching_role() {
		$user_id = $this->factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $user_id );

		$output = do_shortcode( '[wpum_restrict_to_user_roles roles="editor"]Editor Content[/wpum_restrict_to_user_roles]' );

		$this->assertStringContainsString( 'Editor Content', $output, 'Content should be visible to users with matching role.' );
	}

	/**
	 * Test that [wpum_restrict_to_user_roles] returns empty for a non-matching role
	 * when show_message is 'no'.
	 */
	public function test_restrict_to_roles_hides_for_non_matching_role() {
		$user_id = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $user_id );

		$ob_level_before = ob_get_level();
		$output = do_shortcode( '[wpum_restrict_to_user_roles roles="editor" show_message="no"]Editor Content[/wpum_restrict_to_user_roles]' );
		while ( ob_get_level() > $ob_level_before ) {
			ob_end_clean();
		}

		$this->assertEmpty( $output, 'Content should be empty for non-matching role with show_message=no.' );
	}
}
