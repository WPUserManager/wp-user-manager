<?php
/**
 * The multiple roles field on the user screens: wpum_action_profile_update().
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ProfileRolesUpdateTest extends WPUMTestCase {

	protected $member;

	protected $promoter;

	public function _setUp() {
		parent::_setUp();

		wpum_update_option( 'allow_multiple_user_roles', true );

		// Below an administrator, but able to promote users to the roles it may edit.
		add_role( 'promoter', 'Promoter', array( 'read' => true, 'list_users' => true, 'edit_users' => true, 'promote_users' => true ) );

		add_filter( 'editable_roles', function ( $roles ) {
			if ( current_user_can( 'manage_options' ) ) {
				return $roles;
			}

			return array_intersect_key( $roles, array_flip( array( 'subscriber', 'contributor' ) ) );
		} );

		$this->member   = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$this->promoter = $this->factory()->user->create( array( 'role' => 'promoter' ) );
	}

	public function _tearDown() {
		unset( $_POST['_wpum_user_roles'] );
		wpum_delete_option( 'allow_multiple_user_roles' );
		remove_role( 'promoter' );

		parent::_tearDown();
	}

	protected function post_roles( $user_id, $roles ) {
		$_POST['_wpum_user_roles'] = $roles;

		wpum_action_profile_update( $user_id );

		return get_userdata( $user_id )->roles;
	}

	public function test_an_administrator_can_assign_several_roles() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$roles = $this->post_roles( $this->member, 'subscriber|editor' );

		$this->assertEqualsCanonicalizing( array( 'subscriber', 'editor' ), $roles );
	}

	public function test_an_administrator_can_remove_a_role() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );
		get_userdata( $this->member )->add_role( 'editor' );

		$roles = $this->post_roles( $this->member, 'editor' );

		$this->assertSame( array( 'editor' ), array_values( $roles ) );
	}

	public function test_a_role_the_user_may_not_assign_is_not_added() {
		wp_set_current_user( $this->promoter );

		$roles = $this->post_roles( $this->member, 'subscriber|administrator' );

		$this->assertSame( array( 'subscriber' ), array_values( $roles ) );
	}

	public function test_a_role_the_user_may_assign_is_added() {
		wp_set_current_user( $this->promoter );

		$roles = $this->post_roles( $this->member, 'subscriber|contributor' );

		$this->assertEqualsCanonicalizing( array( 'subscriber', 'contributor' ), $roles );
	}

	public function test_a_role_the_user_may_not_assign_is_not_removed() {
		wp_set_current_user( $this->promoter );
		get_userdata( $this->member )->add_role( 'editor' );

		$roles = $this->post_roles( $this->member, 'subscriber' );

		$this->assertEqualsCanonicalizing( array( 'subscriber', 'editor' ), $roles );
	}

	public function test_a_role_can_be_removed_when_only_a_role_above_theirs_stays() {
		wp_set_current_user( $this->promoter );
		get_userdata( $this->member )->add_role( 'editor' );

		$roles = $this->post_roles( $this->member, 'editor' );

		$this->assertSame( array( 'editor' ), array_values( $roles ) );
	}

	/**
	 * WordPress saves its own role field first, which can drop roles before this runs.
	 */
	public function test_a_role_above_theirs_comes_back_if_the_save_dropped_it() {
		wp_set_current_user( $this->promoter );
		$member = get_userdata( $this->member );
		$member->add_role( 'editor' );
		$before = clone $member;

		$member->remove_role( 'editor' );

		$_POST['_wpum_user_roles'] = 'subscriber';
		wpum_action_profile_update( $this->member, $before );

		$this->assertEqualsCanonicalizing( array( 'subscriber', 'editor' ), get_userdata( $this->member )->roles );
	}

	public function test_an_empty_submission_changes_nothing() {
		wp_set_current_user( $this->promoter );
		get_userdata( $this->member )->add_role( 'editor' );

		$roles = $this->post_roles( $this->member, '' );

		$this->assertEqualsCanonicalizing( array( 'subscriber', 'editor' ), $roles );
	}

	public function test_a_role_that_does_not_exist_is_not_added() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$roles = $this->post_roles( $this->member, 'subscriber|no_such_role' );

		$this->assertSame( array( 'subscriber' ), array_values( $roles ) );
	}

	public function test_someone_who_cannot_promote_changes_nothing() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'editor' ) ) );

		$roles = $this->post_roles( $this->member, 'administrator' );

		$this->assertSame( array( 'subscriber' ), array_values( $roles ) );
	}

	public function test_a_visitor_changes_nothing() {
		wp_set_current_user( 0 );

		$roles = $this->post_roles( $this->member, 'administrator' );

		$this->assertSame( array( 'subscriber' ), array_values( $roles ) );
	}

	public function test_nothing_changes_with_the_setting_off() {
		wpum_delete_option( 'allow_multiple_user_roles' );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$roles = $this->post_roles( $this->member, 'editor' );

		$this->assertSame( array( 'subscriber' ), array_values( $roles ) );
	}
}
