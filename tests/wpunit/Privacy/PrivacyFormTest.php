<?php
/**
 * Tests for the privacy form.
 *
 * Note: The submit_handler() uses filter_input(INPUT_POST) for nonce verification,
 * which does not read from the $_POST superglobal in CLI testing.
 * Therefore we test the form structure and the logged-in/logged-out behavior.
 */

require_once __DIR__ . '/PrivacyTestCase.php';

class PrivacyFormTest extends PrivacyTestCase {

	/**
	 * Test that a logged-out user cannot access the privacy form.
	 */
	public function test_logged_out_user_cannot_access_privacy_form() {
		wp_set_current_user( 0 );

		$form  = \WPUM_Form_Privacy::instance();
		$steps = $form->get_steps();

		$this->assertEmpty( $steps, 'Steps should be empty when user is not logged in.' );
	}

	/**
	 * Test that a logged-in user gets a privacy form with steps.
	 */
	public function test_logged_in_user_has_form_steps() {
		$this->create_and_login_user();

		$form  = \WPUM_Form_Privacy::instance();
		$steps = $form->get_steps();

		$this->assertNotEmpty( $steps, 'Steps should not be empty when user is logged in.' );
		$this->assertArrayHasKey( 'submit', $steps, 'Should have a submit step.' );
	}

	/**
	 * Test that the privacy form has the correct fields when user is logged in.
	 */
	public function test_privacy_form_has_expected_fields() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Privacy::instance();

		// init_fields is called internally when get_fields is called
		// but we need to make sure fields are initialized.
		$form->init_fields();

		$fields = $form->get_fields( 'privacy' );

		$this->assertArrayHasKey( 'hide_profile_guests', $fields, 'Should have hide_profile_guests field.' );
		$this->assertArrayHasKey( 'hide_profile_members', $fields, 'Should have hide_profile_members field.' );
	}

	/**
	 * Test that both privacy fields are checkboxes.
	 */
	public function test_privacy_fields_are_checkboxes() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Privacy::instance();
		$form->init_fields();

		$fields = $form->get_fields( 'privacy' );

		$this->assertEquals( 'checkbox', $fields['hide_profile_guests']['type'], 'hide_profile_guests should be a checkbox.' );
		$this->assertEquals( 'checkbox', $fields['hide_profile_members']['type'], 'hide_profile_members should be a checkbox.' );
	}

	/**
	 * Test that privacy fields are not required.
	 */
	public function test_privacy_fields_are_not_required() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Privacy::instance();
		$form->init_fields();

		$fields = $form->get_fields( 'privacy' );

		$this->assertFalse( $fields['hide_profile_guests']['required'], 'hide_profile_guests should not be required.' );
		$this->assertFalse( $fields['hide_profile_members']['required'], 'hide_profile_members should not be required.' );
	}

	/**
	 * Test that the form name is correct.
	 */
	public function test_form_name_is_correct() {
		$this->create_and_login_user();

		$form = \WPUM_Form_Privacy::instance();

		$this->assertEquals( 'privacy', $form->get_form_name(), 'Form name should be "privacy".' );
	}
}
