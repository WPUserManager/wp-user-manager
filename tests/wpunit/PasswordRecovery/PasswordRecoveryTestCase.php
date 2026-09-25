<?php
/**
 * Base test case for WPUM password recovery form tests.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

abstract class PasswordRecoveryTestCase extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		// Load the password recovery form class.
		if ( ! class_exists( 'WPUM_Form_Password_Recovery' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-password-recovery.php';
		}
	}

	public function _tearDown() {
		// Reset singleton.
		$this->reset_singleton( 'WPUM_Form_Password_Recovery', 'instance' );

		// Remove filters the constructor adds.
		remove_all_filters( 'submit_wpum_form_validate_fields' );
		remove_all_actions( 'wp' );

		parent::_tearDown();
	}

	/**
	 * Simulate a password recovery form submission (step 1 - submit username/email).
	 *
	 * @param string $username_email The username or email to submit.
	 * @param bool   $include_nonce  Whether to include a valid nonce.
	 * @param bool   $include_submit Whether to include the submit button.
	 *
	 * @return \WPUM_Form_Password_Recovery
	 */
	protected function submit_recovery( $username_email = '', $include_nonce = true, $include_submit = true ) {
		$_POST = array(
			'username_email' => $username_email,
		);

		if ( $include_nonce ) {
			$_POST['password_recovery_nonce'] = wp_create_nonce( 'verify_password_recovery_form' );
		}

		if ( $include_submit ) {
			$_POST['submit_password_recovery'] = 'Reset Password';
		}

		$form = \WPUM_Form_Password_Recovery::instance();
		$form->submit_handler();

		return $form;
	}
}
