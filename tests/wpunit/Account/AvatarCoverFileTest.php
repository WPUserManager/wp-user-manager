<?php
/**
 * Tests for avatar and cover image file handling during account updates.
 *
 * Regression tests for #439: account updates must not fail when avatar or
 * cover image files no longer exist on disk (stale meta after migrations,
 * cleanup plugins, etc).
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/439
 * @see https://github.com/WPUserManager/wp-user-manager/pull/442
 */

require_once __DIR__ . '/AccountTestCase.php';

class AvatarCoverFileTest extends AccountTestCase {

	public function _setUp() {
		parent::_setUp();

		// Enable custom avatars for all tests.
		wpum_update_option( 'custom_avatars', true );
	}

	public function _tearDown() {
		// Clean up any test files.
		foreach ( glob( $this->upload_dir . '/test-avatar-*' ) as $f ) {
			@unlink( $f );
		}
		foreach ( glob( $this->upload_dir . '/test-cover-*' ) as $f ) {
			@unlink( $f );
		}

		parent::_tearDown();
	}

	// ─── Stale avatar meta (#439 regression) ────────────────────────

	public function test_account_update_succeeds_when_avatar_file_missing() {
		$user_id = $this->create_and_login_user();
		$user    = get_user_by( 'id', $user_id );

		// Set meta pointing to a file that does not exist.
		update_user_meta( $user_id, '_current_user_avatar_path', '/nonexistent/path/avatar.jpg' );

		$_POST['current_user_avatar'] = 'http://example.com/avatar.jpg';

		$result = $this->harness->do_update( $user, $this->build_values() );

		$this->assertEquals( $user_id, $result );
	}

	public function test_account_update_succeeds_when_cover_file_missing() {
		$user_id = $this->create_and_login_user();
		$user    = get_user_by( 'id', $user_id );

		// Set meta pointing to a file that does not exist.
		update_user_meta( $user_id, '_user_cover_path', '/nonexistent/path/cover.jpg' );

		$_POST['current_user_cover'] = 'http://example.com/cover.jpg';

		$result = $this->harness->do_update( $user, $this->build_values() );

		$this->assertEquals( $user_id, $result );
	}

	// ─── Avatar deletion when file exists ───────────────────────────

	public function test_old_avatar_deleted_when_new_one_uploaded() {
		$user_id  = $this->create_and_login_user();
		$user     = get_user_by( 'id', $user_id );
		$old_file = $this->create_temp_upload( 'test-avatar-old-' . wp_rand() . '.jpg' );

		update_user_meta( $user_id, '_current_user_avatar_path', $old_file );

		$_POST['current_user_avatar'] = 'http://example.com/old-avatar.jpg';

		$values = $this->build_values( array(
			'user_avatar' => array(
				'url'  => 'http://example.com/new-avatar.jpg',
				'path' => $this->upload_dir . '/new-avatar.jpg',
			),
		) );

		$this->harness->do_update( $user, $values );

		$this->assertFileDoesNotExist( $old_file );
	}

	public function test_avatar_removed_when_cleared() {
		$user_id  = $this->create_and_login_user();
		$user     = get_user_by( 'id', $user_id );
		$old_file = $this->create_temp_upload( 'test-avatar-remove-' . wp_rand() . '.jpg' );

		update_user_meta( $user_id, '_current_user_avatar_path', $old_file );

		// No current_user_avatar in POST = user cleared the avatar.
		$_POST['current_user_avatar'] = '';

		$result = $this->harness->do_update( $user, $this->build_values() );

		$this->assertEquals( $user_id, $result );
		$this->assertFileDoesNotExist( $old_file );
		$this->assertEmpty( get_user_meta( $user_id, '_current_user_avatar_path', true ) );
	}

	public function test_remove_avatar_fires_hook() {
		$user_id  = $this->create_and_login_user();
		$user     = get_user_by( 'id', $user_id );
		$old_file = $this->create_temp_upload( 'test-avatar-hook-' . wp_rand() . '.jpg' );

		update_user_meta( $user_id, '_current_user_avatar_path', $old_file );

		$_POST['current_user_avatar'] = '';

		$fired = false;
		add_action( 'wpum_user_update_remove_avatar', function () use ( &$fired ) {
			$fired = true;
		} );

		$this->harness->do_update( $user, $this->build_values() );

		$this->assertTrue( $fired );
	}

	// ─── Cover deletion when file exists ────────────────────────────

	public function test_old_cover_deleted_when_new_one_uploaded() {
		$user_id  = $this->create_and_login_user();
		$user     = get_user_by( 'id', $user_id );
		$old_file = $this->create_temp_upload( 'test-cover-old-' . wp_rand() . '.jpg' );

		update_user_meta( $user_id, '_user_cover_path', $old_file );

		$_POST['current_user_cover'] = 'http://example.com/old-cover.jpg';

		$values = $this->build_values( array(
			'user_cover' => array(
				'url'  => 'http://example.com/new-cover.jpg',
				'path' => $this->upload_dir . '/new-cover.jpg',
			),
		) );

		$this->harness->do_update( $user, $values );

		$this->assertFileDoesNotExist( $old_file );
	}

	public function test_cover_removed_when_cleared() {
		$user_id  = $this->create_and_login_user();
		$user     = get_user_by( 'id', $user_id );
		$old_file = $this->create_temp_upload( 'test-cover-remove-' . wp_rand() . '.jpg' );

		update_user_meta( $user_id, '_user_cover_path', $old_file );

		$_POST['current_user_cover'] = '';

		$result = $this->harness->do_update( $user, $this->build_values() );

		$this->assertEquals( $user_id, $result );
		$this->assertFileDoesNotExist( $old_file );
		$this->assertEmpty( get_user_meta( $user_id, '_user_cover_path', true ) );
	}

	// ─── No-op when nothing changed ─────────────────────────────────

	public function test_existing_avatar_not_deleted_when_same_file_kept() {
		$user_id  = $this->create_and_login_user();
		$user     = get_user_by( 'id', $user_id );
		$file     = $this->create_temp_upload( 'test-avatar-keep-' . wp_rand() . '.jpg' );
		$file_url = 'http://example.com/avatar-keep.jpg';

		update_user_meta( $user_id, '_current_user_avatar_path', $file );

		$_POST['current_user_avatar'] = $file_url;

		// Same URL in both POST and values = no change.
		$values = $this->build_values( array(
			'user_avatar' => array(
				'url'  => $file_url,
				'path' => $file,
			),
		) );

		$this->harness->do_update( $user, $values );

		$this->assertFileExists( $file );
	}

	// ─── Stale meta does not block unrelated updates ────────────────

	public function test_stale_avatar_meta_does_not_block_name_update() {
		$user_id = $this->create_and_login_user();
		$user    = get_user_by( 'id', $user_id );

		update_user_meta( $user_id, '_current_user_avatar_path', '/gone/avatar.jpg' );

		$_POST['current_user_avatar'] = 'http://example.com/gone-avatar.jpg';

		$values = $this->build_values( array(
			'user_firstname' => 'Updated Name',
		) );

		$result = $this->harness->do_update( $user, $values );

		$this->assertEquals( $user_id, $result );
		$this->assertEquals( 'Updated Name', get_user_meta( $user_id, 'first_name', true ) );
	}

	public function test_stale_cover_meta_does_not_block_name_update() {
		$user_id = $this->create_and_login_user();
		$user    = get_user_by( 'id', $user_id );

		update_user_meta( $user_id, '_user_cover_path', '/gone/cover.jpg' );

		$_POST['current_user_cover'] = 'http://example.com/gone-cover.jpg';

		$values = $this->build_values( array(
			'user_firstname' => 'Another Name',
		) );

		$result = $this->harness->do_update( $user, $values );

		$this->assertEquals( $user_id, $result );
		$this->assertEquals( 'Another Name', get_user_meta( $user_id, 'first_name', true ) );
	}
}
