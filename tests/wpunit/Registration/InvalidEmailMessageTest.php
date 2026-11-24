<?php
/**
 * The registration form's message for an invalid email address.
 */

require_once __DIR__ . '/RegistrationTestCase.php';

class InvalidEmailMessageTest extends RegistrationTestCase {

	public function _setUp() {
		parent::_setUp();
		$this->set_form_errors( array() );
	}

	public function _tearDown() {
		$this->set_form_errors( array() );
		parent::_tearDown();
	}

	protected function form_errors() {
		$ref = new \ReflectionProperty( \WPUM_Form_Registration::instance(), 'errors' );
		$ref->setAccessible( true );

		return $ref->getValue( \WPUM_Form_Registration::instance() );
	}

	protected function set_form_errors( $errors ) {
		$ref = new \ReflectionProperty( \WPUM_Form_Registration::instance(), 'errors' );
		$ref->setAccessible( true );
		$ref->setValue( \WPUM_Form_Registration::instance(), $errors );
	}

	public function test_invalid_email_message_does_not_repeat_the_value() {
		$value = '[wpum_test_value src=example] not-an-email';
		$data  = $this->get_valid_registration_data( array(
			'register' => array( 'user_email' => $value ),
		) );

		$this->assertFalse( $this->submit_registration( $data ) );

		$errors = $this->form_errors();
		$this->assertContains( 'Please enter a valid email address.', $errors );

		foreach ( $errors as $error ) {
			$this->assertStringNotContainsString( 'wpum_test_value', $error );
			$this->assertStringNotContainsString( '[', $error );
		}
	}
}
