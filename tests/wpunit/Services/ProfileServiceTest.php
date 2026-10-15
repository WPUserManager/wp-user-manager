<?php

/**
 * Test the functions that validate and save a user's profile values.
 *
 * The account form calls these through the account trait. Called directly,
 * without the avatar and cover the form shows, they leave both alone.
 */
class ProfileServiceTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $user_id;

	public function _setUp() {
		parent::_setUp();

		$this->user_id = $this->factory()->user->create( array(
			'user_login' => 'profile_service_user',
			'user_email' => 'profile_service@example.com',
			'role'       => 'subscriber',
		) );
	}

	/**
	 * Create a file in the uploads directory and record it as the user's cover.
	 *
	 * @return string The file path.
	 */
	protected function give_user_a_cover() {
		$path = wp_upload_dir()['basedir'] . '/profile-service-cover.jpg';
		file_put_contents( $path, 'cover' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		update_user_meta( $this->user_id, '_user_cover_path', $path );

		return $path;
	}

	public function test_saves_core_profile_values() {
		$result = wpum_update_profile_values( $this->user_id, array(
			'user_firstname'   => 'Ada',
			'user_lastname'    => 'Lovelace',
			'user_email'       => 'ada@example.com',
			'user_nickname'    => 'countess',
			'user_website'     => 'https://example.com',
			'user_description' => 'Mathematician',
		) );

		$user = get_userdata( $this->user_id );
		$this->assertSame( $this->user_id, $result );
		$this->assertSame( 'Ada', $user->first_name );
		$this->assertSame( 'Lovelace', $user->last_name );
		$this->assertSame( 'ada@example.com', $user->user_email );
		$this->assertSame( 'countess', $user->nickname );
		$this->assertSame( 'https://example.com', $user->user_url );
		$this->assertSame( 'Mathematician', $user->description );
	}

	public function test_accepts_a_user_object() {
		$result = wpum_update_profile_values( get_userdata( $this->user_id ), array( 'user_firstname' => 'Grace' ) );

		$this->assertSame( $this->user_id, $result );
		$this->assertSame( 'Grace', get_userdata( $this->user_id )->first_name );
	}

	public function test_only_given_values_change() {
		wp_update_user( array(
			'ID'        => $this->user_id,
			'last_name' => 'Hopper',
		) );

		wpum_update_profile_values( $this->user_id, array( 'user_firstname' => 'Grace' ) );

		$this->assertSame( 'Hopper', get_userdata( $this->user_id )->last_name );
	}

	public function test_strips_tags_and_serialized_values() {
		wpum_update_profile_values( $this->user_id, array(
			'user_firstname' => '<b>Ada</b>',
			'user_lastname'  => serialize( array( 'x' ) ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize
		) );

		$user = get_userdata( $this->user_id );
		$this->assertSame( 'Ada', $user->first_name );
		$this->assertSame( '', $user->last_name );
	}

	public function test_display_name_from_choice() {
		wpum_update_profile_values( $this->user_id, array(
			'user_firstname'   => 'Ada',
			'user_lastname'    => 'Lovelace',
			'user_displayname' => 'display_lastfirst',
		) );

		$this->assertSame( 'Lovelace Ada', get_userdata( $this->user_id )->display_name );
	}

	public function test_display_name_uses_stored_names_not_given() {
		wp_update_user( array(
			'ID'        => $this->user_id,
			'last_name' => 'Hopper',
		) );

		wpum_update_profile_values( $this->user_id, array(
			'user_firstname'   => 'Grace',
			'user_displayname' => 'display_firstlast',
		) );

		$this->assertSame( 'Grace Hopper', get_userdata( $this->user_id )->display_name );
	}

	public function test_display_name_keeps_names_given_as_empty() {
		wp_update_user( array(
			'ID'        => $this->user_id,
			'last_name' => 'Hopper',
		) );

		wpum_update_profile_values( $this->user_id, array(
			'user_firstname'   => 'Grace',
			'user_lastname'    => '',
			'user_displayname' => 'display_firstlast',
		) );

		$this->assertSame( 'Grace', get_userdata( $this->user_id )->display_name );
	}

	public function test_display_name_argument_is_used_as_given() {
		wpum_update_profile_values( $this->user_id, array( 'user_displayname' => 'display_firstname' ), array( 'display_name' => 'The Countess' ) );

		$this->assertSame( 'The Countess', get_userdata( $this->user_id )->display_name );
	}

	public function test_display_name_choices() {
		$user   = get_userdata( $this->user_id );
		$values = array(
			'user_firstname' => 'Ada',
			'user_lastname'  => 'Lovelace',
			'user_nickname'  => 'countess',
		);

		$this->assertSame( 'countess', wpum_get_display_name_from_choice( $user, $values, 'display_nickname' ) );
		$this->assertSame( 'Ada', wpum_get_display_name_from_choice( $user, $values, 'display_firstname' ) );
		$this->assertSame( 'Lovelace', wpum_get_display_name_from_choice( $user, $values, 'display_lastname' ) );
		$this->assertSame( 'Ada Lovelace', wpum_get_display_name_from_choice( $user, $values, 'display_firstlast' ) );
		$this->assertSame( 'Lovelace Ada', wpum_get_display_name_from_choice( $user, $values, 'display_lastfirst' ) );
		$this->assertSame( 'profile_service_user', wpum_get_display_name_from_choice( $user, $values, 'anything_else' ) );
	}

	public function test_missing_user_returns_error() {
		$result = wpum_update_profile_values( 999999, array( 'user_firstname' => 'Ada' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_profile_user_not_found', $result->get_error_code() );
	}

	public function test_email_used_by_another_user_returns_error() {
		$this->factory()->user->create( array( 'user_email' => 'taken@example.com' ) );

		$result = wpum_update_profile_values( $this->user_id, array( 'user_email' => 'taken@example.com' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'profile_service@example.com', get_userdata( $this->user_id )->user_email );
	}

	public function test_without_form_state_cover_is_left_alone() {
		$path = $this->give_user_a_cover();

		wpum_update_profile_values( $this->user_id, array( 'user_firstname' => 'Ada' ) );

		$this->assertFileExists( $path );
		$this->assertSame( $path, get_user_meta( $this->user_id, '_user_cover_path', true ) );

		wp_delete_file( $path );
	}

	public function test_without_form_state_new_cover_replaces_stored_file() {
		$old_path = $this->give_user_a_cover();
		$new_path = wp_upload_dir()['basedir'] . '/profile-service-cover-new.jpg';
		file_put_contents( $new_path, 'new cover' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		wpum_update_profile_values( $this->user_id, array(
			'user_cover' => array(
				'url'  => 'http://example.com/profile-service-cover-new.jpg',
				'path' => $new_path,
			),
		) );

		$this->assertFileDoesNotExist( $old_path );
		$this->assertFileExists( $new_path );
		$this->assertSame( $new_path, get_user_meta( $this->user_id, '_user_cover_path', true ) );

		wp_delete_file( $new_path );
	}

	public function test_replaced_file_check() {
		$path = $this->give_user_a_cover();

		$this->assertTrue( wpum_is_replaced_profile_file( $path, array( 'path' => '/elsewhere.jpg' ) ) );
		$this->assertFalse( wpum_is_replaced_profile_file( $path, array( 'path' => $path ) ), 'The same file is not replaced.' );
		$this->assertFalse( wpum_is_replaced_profile_file( '/missing.jpg', array( 'path' => '/elsewhere.jpg' ) ) );

		wp_delete_file( $path );
	}

	/**
	 * The account form posts the cover it showed. An empty one means the user
	 * removed it, so the file goes.
	 */
	public function test_form_with_no_current_cover_removes_it() {
		$path = $this->give_user_a_cover();

		wpum_update_profile_values( $this->user_id, array( 'user_firstname' => 'Ada' ), array(
			'current_avatar' => false,
			'current_cover'  => false,
		) );

		$this->assertFileDoesNotExist( $path );
		$this->assertEmpty( get_user_meta( $this->user_id, '_user_cover_path', true ) );
	}

	public function test_partial_form_keeps_cover() {
		$path = $this->give_user_a_cover();

		wpum_update_profile_values( $this->user_id, array( 'user_firstname' => 'Ada' ), array(
			'current_avatar' => false,
			'current_cover'  => false,
			'partial'        => true,
		) );

		$this->assertFileExists( $path );

		wp_delete_file( $path );
	}

	/*
	 * Validation.
	 */

	public function test_nickname_used_by_another_user_is_rejected() {
		$other = $this->factory()->user->create();
		update_user_meta( $other, 'nickname', 'taken_nickname' );

		$result = wpum_validate_profile_values( $this->user_id, array(
			'user_nickname'    => 'taken_nickname',
			'user_displayname' => 'display_nickname',
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'displayname-unique-validation-error', $result->get_error_code() );
	}

	public function test_own_nickname_is_allowed() {
		update_user_meta( $this->user_id, 'nickname', 'my_nickname' );

		$this->assertTrue( wpum_validate_profile_values( $this->user_id, array(
			'user_nickname'    => 'my_nickname',
			'user_displayname' => 'display_nickname',
		) ) );
	}

	public function test_values_without_nickname_pass() {
		$this->assertTrue( wpum_validate_profile_values( $this->user_id, array( 'user_firstname' => 'Ada' ) ) );
	}

	public function test_strict_rejects_invalid_email() {
		$values = array( 'user_email' => 'not-an-email' );

		$this->assertTrue( wpum_validate_profile_values( $this->user_id, $values ), 'The account form does not check the format.' );

		$result = wpum_validate_profile_values( $this->user_id, $values, array( 'strict' => true ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wpum_profile_invalid_email', $result->get_error_code() );
	}
}
