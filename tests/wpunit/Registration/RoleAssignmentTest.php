<?php
/**
 * Tests for registration role assignment.
 */

namespace WPUM\Tests\Registration;

class RoleAssignmentTest extends RegistrationTestCase {

	public function test_user_gets_form_configured_role() {
		// Set the form's role to 'editor'.
		if ( $this->default_form ) {
			$this->default_form->update_meta( 'role', 'editor' );
		}

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertTrue(
			in_array( 'editor', $user->roles, true ),
			'User should be assigned the role configured on the registration form'
		);

		// Reset to default.
		if ( $this->default_form ) {
			$this->default_form->update_meta( 'role', get_option( 'default_role' ) );
		}
	}

	public function test_user_gets_default_role_when_form_has_no_role() {
		$default_role = get_option( 'default_role' );

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertTrue(
			in_array( $default_role, $user->roles, true ),
			'User should get the WordPress default role when form has no specific role'
		);
	}

	public function test_role_select_with_valid_role() {
		if ( ! $this->default_form ) {
			$this->markTestSkipped( 'No default registration form found.' );
		}

		// Enable role select and set allowed roles.
		$this->default_form->update_setting( 'allow_role_select', true );
		$this->default_form->update_setting( 'register_roles', array( 'subscriber', 'contributor' ) );

		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'role' => 'contributor' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertTrue(
			in_array( 'contributor', $user->roles, true ),
			'User should be assigned the selected role from the dropdown'
		);

		// Reset.
		$this->default_form->update_setting( 'allow_role_select', false );
	}

	public function test_role_select_with_disallowed_role_fails() {
		if ( ! $this->default_form ) {
			$this->markTestSkipped( 'No default registration form found.' );
		}

		// Enable role select but only allow 'subscriber'.
		$this->default_form->update_setting( 'allow_role_select', true );
		$this->default_form->update_setting( 'register_roles', array( 'subscriber' ) );

		$data    = $this->get_valid_registration_data( array(
			'register' => array( 'role' => 'administrator' ),
		) );
		$user_id = $this->submit_registration( $data );

		$this->assertFalse( $user_id, 'Registration with disallowed role should fail' );

		// Reset.
		$this->default_form->update_setting( 'allow_role_select', false );
	}
}
