<?php
/**
 * Tests for issue #439: Account update fails when avatar path doesn't exist.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/439
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class Issue439Test extends WPUMTestCase {

	/**
	 * @test
	 * @group regression
	 */
	public function it_should_allow_account_update_when_avatar_path_is_missing() {
		// Arrange: Create a test user.
		$user_id = $this->factory()->user->create( array(
			'user_login' => 'testuser439',
			'user_email' => 'test439@example.com',
		) );

		// Simulate an avatar path stored in user meta but the file doesn't exist.
		$non_existent_path = '/tmp/wpum-uploads/non-existent-avatar.jpg';
		update_user_meta( $user_id, '_current_user_avatar_path', $non_existent_path );

		// Enable custom avatars option.
		update_option( 'wpum_custom_avatars', true );

		// Get the user.
		$user = get_user_by( 'id', $user_id );

		// Mock the trait method by creating a minimal test class.
		require_once WPUM_PLUGIN_DIR . 'includes/forms/trait-wpum-account.php';

		$account_updater = new class() {
			use WPUM_Form_Account;

			public function test_update( $user, $values ) {
				return $this->update_account_values( $user, $values );
			}
		};

		// Act: Try to update the account with the non-existent avatar path.
		$values = array(
			'account' => array(
				'user_firstname' => 'Updated',
			),
		);

		// Simulate the POST data that would come from the form.
		$_POST['current_user_avatar'] = '';

		try {
			$result = $account_updater->test_update( $user, $values );

			// Assert: The update should succeed (no exception thrown).
			$this->assertIsInt( $result, 'Account update should return user ID when avatar path is missing.' );
			$this->assertEquals( $user_id, $result, 'Returned user ID should match the original user ID.' );

			// Verify the user data was actually updated.
			$updated_user = get_user_by( 'id', $user_id );
			$this->assertEquals( 'Updated', $updated_user->first_name, 'First name should be updated.' );

		} catch ( Exception $e ) {
			// If we get here, the bug still exists.
			$this->fail( 'Account update should not throw exception when avatar path is missing. Got: ' . $e->getMessage() );
		}
	}

	/**
	 * @test
	 * @group regression
	 */
	public function it_should_allow_account_update_when_cover_path_is_missing() {
		// Arrange: Create a test user.
		$user_id = $this->factory()->user->create( array(
			'user_login' => 'testuser439cover',
			'user_email' => 'test439cover@example.com',
		) );

		// Simulate a cover path stored in user meta but the file doesn't exist.
		$non_existent_path = '/tmp/wpum-uploads/non-existent-cover.jpg';
		update_user_meta( $user_id, '_user_cover_path', $non_existent_path );

		// Get the user.
		$user = get_user_by( 'id', $user_id );

		// Mock the trait method.
		require_once WPUM_PLUGIN_DIR . 'includes/forms/trait-wpum-account.php';

		$account_updater = new class() {
			use WPUM_Form_Account;

			public function test_update( $user, $values ) {
				return $this->update_account_values( $user, $values );
			}
		};

		// Act: Try to update the account with the non-existent cover path.
		$values = array(
			'account' => array(
				'user_lastname' => 'UpdatedLast',
			),
		);

		// Simulate no cover in POST.
		$_POST['current_user_cover'] = '';

		try {
			$result = $account_updater->test_update( $user, $values );

			// Assert: The update should succeed.
			$this->assertIsInt( $result, 'Account update should return user ID when cover path is missing.' );
			$this->assertEquals( $user_id, $result, 'Returned user ID should match the original user ID.' );

			// Verify the user data was actually updated.
			$updated_user = get_user_by( 'id', $user_id );
			$this->assertEquals( 'UpdatedLast', $updated_user->last_name, 'Last name should be updated.' );

		} catch ( Exception $e ) {
			$this->fail( 'Account update should not throw exception when cover path is missing. Got: ' . $e->getMessage() );
		}
	}

	/**
	 * @test
	 * @group security
	 */
	public function it_should_block_directory_traversal_when_avatar_path_exists() {
		// Arrange: Create a test user.
		$user_id = $this->factory()->user->create( array(
			'user_login' => 'testuser439security',
			'user_email' => 'test439security@example.com',
		) );

		// Enable custom avatars.
		update_option( 'wpum_custom_avatars', true );

		// Create a malicious path that exists but is outside upload dir.
		$malicious_path = WPUM_PLUGIN_DIR . 'wp-user-manager.php';
		update_user_meta( $user_id, '_current_user_avatar_path', $malicious_path );

		$user = get_user_by( 'id', $user_id );

		require_once WPUM_PLUGIN_DIR . 'includes/forms/trait-wpum-account.php';

		$account_updater = new class() {
			use WPUM_Form_Account;

			public function test_update( $user, $values ) {
				return $this->update_account_values( $user, $values );
			}
		};

		$values = array(
			'account' => array(
				'user_firstname' => 'Hacker',
			),
		);

		$_POST['current_user_avatar'] = '';

		// Act & Assert: Should throw exception because the path exists but is outside upload dir.
		$this->expectException( Exception::class );
		$this->expectExceptionMessage( 'Path error with existing avatar' );

		$account_updater->test_update( $user, $values );
	}
}
