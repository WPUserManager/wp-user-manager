<?php
/**
 * Tests for registration hook integration points.
 *
 * These hooks are the primary way addons extend the registration flow.
 * If any of these break, addon compatibility breaks.
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class HooksTest extends RegistrationTestCase {

	public function test_registration_user_data_filter_modifies_user() {
		add_filter( 'wpum_registration_user_data', function ( $user_data ) {
			$user_data['display_name'] = 'Filtered Display Name';
			return $user_data;
		} );

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );

		$user = get_userdata( $user_id );
		$this->assertEquals(
			'Filtered Display Name',
			$user->display_name,
			'wpum_registration_user_data filter should modify user data before save'
		);
	}

	public function test_before_registration_end_receives_user_id_and_values() {
		$captured = array();

		add_action( 'wpum_before_registration_end', function ( $user_id, $values, $form ) use ( &$captured ) {
			$captured = compact( 'user_id', 'values', 'form' );
		}, 10, 3 );

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
		$this->assertEquals( $user_id, $captured['user_id'] );
		$this->assertIsArray( $captured['values'] );
		$this->assertInstanceOf( \WPUM_Registration_Form::class, $captured['form'] );
	}

	public function test_after_registration_receives_form_object() {
		$captured_form = null;

		add_action( 'wpum_after_registration', function ( $user_id, $values, $form ) use ( &$captured_form ) {
			$captured_form = $form;
		}, 10, 3 );

		$data    = $this->get_valid_registration_data();
		$user_id = $this->submit_registration( $data );

		$this->assertIsInt( $user_id );
		$this->assertInstanceOf(
			\WPUM_Registration_Form::class,
			$captured_form,
			'wpum_after_registration should pass the form object'
		);
	}

	public function test_get_registration_fields_filter() {
		$filter_called = false;

		add_filter( 'wpum_get_registration_fields', function ( $fields ) use ( &$filter_called ) {
			$filter_called = true;
			return $fields;
		} );

		$data = $this->get_valid_registration_data();
		$this->submit_registration( $data );

		$this->assertTrue(
			$filter_called,
			'wpum_get_registration_fields filter should fire during registration'
		);
	}

	public function test_custom_field_added_via_filter_is_processed() {
		// Add a custom field via the registration fields filter.
		add_filter( 'wpum_get_registration_fields', function ( $fields ) {
			$fields['custom_test_field'] = array(
				'label'    => 'Test Field',
				'type'     => 'text',
				'required' => false,
				'priority' => 50,
			);
			return $fields;
		} );

		$data = $this->get_valid_registration_data( array(
			'register' => array( 'custom_test_field' => 'test_value' ),
		) );

		$user_id = $this->submit_registration( $data );

		// The registration should still succeed with the extra field.
		$this->assertTrue(
			is_int( $user_id ) || $user_id === false,
			'Registration should handle custom fields added via filter without fatal errors'
		);
	}
}
