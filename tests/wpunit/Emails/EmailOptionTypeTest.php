<?php
/**
 * A non-array wpum_email option must not cause a fatal error.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/228
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailOptionTypeTest extends WPUMTestCase {

	public function _tearDown() {
		delete_option( 'wpum_email' );

		parent::_tearDown();
	}

	public function non_array_values() {
		return array(
			'string'  => array( 'not an array' ),
			'integer' => array( 1 ),
			'true'    => array( true ),
		);
	}

	/**
	 * @dataProvider non_array_values
	 */
	public function test_get_emails_recovers_from_a_non_array_option( $value ) {
		update_option( 'wpum_email', $value );

		$emails = wpum_get_emails();

		$this->assertIsArray( $emails );
		$this->assertArrayHasKey( 'registration_confirmation', $emails );
		$this->assertIsArray( get_option( 'wpum_email' ), 'The option should be repaired.' );
	}

	/**
	 * @dataProvider non_array_values
	 */
	public function test_install_emails_ignores_a_non_array_option( $value ) {
		update_option( 'wpum_email', $value );

		$emails = wpum_install_emails();

		$this->assertSame( array_keys( wpum_get_default_emails() ), array_keys( $emails ) );
	}

	public function test_stored_emails_still_override_defaults() {
		update_option( 'wpum_email', array(
			'registration_confirmation' => array( 'subject' => 'Custom subject' ),
		) );

		$emails = wpum_install_emails();

		$this->assertSame( 'Custom subject', $emails['registration_confirmation']['subject'] );
	}
}
