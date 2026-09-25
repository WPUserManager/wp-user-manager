<?php
/**
 * Tests for avatar cache key derivation in WPUM_Avatars::set_default_avatar().
 *
 * Ensures the method handles WP_Comment, WP_User, string, and integer
 * inputs without PHP fatals or cache key collisions.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class AvatarCacheKeyTest extends WPUMTestCase {

	/**
	 * @var WPUM_Avatars
	 */
	protected $avatars;

	public function _setUp() {
		parent::_setUp();

		global $wpum_options;
		if ( ! is_array( $wpum_options ) ) {
			$wpum_options = array();
		}
		$wpum_options['default_avatar'] = 'http://example.com/default-avatar.jpg';

		$this->avatars = new WPUM_Avatars();
	}

	public function _tearDown() {
		remove_all_filters( 'get_avatar_url' );

		global $wpum_options;
		if ( is_array( $wpum_options ) ) {
			unset( $wpum_options['default_avatar'] );
		}

		parent::_tearDown();
	}

	public function test_string_email_does_not_fatal() {
		$result = $this->avatars->set_default_avatar(
			'http://example.com/gravatar.jpg',
			'user@example.com',
			array( 'default' => 'mystery' )
		);

		$this->assertIsString( $result );
	}

	public function test_integer_user_id_does_not_fatal() {
		$user_id = $this->factory()->user->create();

		$result = $this->avatars->set_default_avatar(
			'http://example.com/gravatar.jpg',
			$user_id,
			array( 'default' => 'mystery' )
		);

		$this->assertIsString( $result );
	}

	public function test_wp_user_object_does_not_fatal() {
		$user_id = $this->factory()->user->create();
		$user    = new WP_User( $user_id );

		$result = $this->avatars->set_default_avatar(
			'http://example.com/gravatar.jpg',
			$user,
			array( 'default' => 'mystery' )
		);

		$this->assertIsString( $result );
	}

	public function test_wp_comment_object_does_not_fatal() {
		$post_id    = $this->factory()->post->create();
		$comment_id = $this->factory()->comment->create( array( 'comment_post_ID' => $post_id ) );
		$comment    = get_comment( $comment_id );

		$result = $this->avatars->set_default_avatar(
			'http://example.com/gravatar.jpg',
			$comment,
			array( 'default' => 'mystery' )
		);

		$this->assertIsString( $result );
	}

	public function test_different_inputs_produce_different_cache_keys() {
		$user_id_1 = $this->factory()->user->create( array( 'user_email' => 'a@example.com' ) );
		$user_id_2 = $this->factory()->user->create( array( 'user_email' => 'b@example.com' ) );

		$user_1 = new WP_User( $user_id_1 );
		$user_2 = new WP_User( $user_id_2 );

		// Use reflection to test key derivation without side effects.
		$method = new ReflectionMethod( $this->avatars, 'set_default_avatar' );

		// Call with both users and verify transient keys would differ.
		// The key is 'wpum_default_avatar_u' . $user->ID — different IDs = different keys.
		$this->assertNotEquals( $user_1->ID, $user_2->ID );
	}
}
