<?php
/**
 * Base test case for WPUM login form tests.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

abstract class LoginTestCase extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		// Load the login form class.
		if ( ! class_exists( 'WPUM_Form_Login' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-login.php';
		}
	}

	public function _tearDown() {
		// Reset singleton.
		$this->reset_singleton( 'WPUM_Form_Login', 'instance' );

		// Remove any filters the login form constructor adds.
		remove_all_actions( 'wp' );

		parent::_tearDown();
	}

	/**
	 * Simulate a login form submission.
	 *
	 * @param string $username
	 * @param string $password
	 * @param bool   $remember
	 * @param bool   $include_submit Whether to include the submit_login key.
	 *
	 * @return \WPUM_Form_Login
	 */
	protected function submit_login( $username = '', $password = '', $remember = false, $include_submit = true ) {
		$_POST = array(
			'username' => $username,
			'password' => $password,
			'remember' => $remember ? '1' : '',
		);

		if ( $include_submit ) {
			$_POST['submit_login'] = 'Login';
		}

		$form = \WPUM_Form_Login::instance();
		$form->submit_handler();

		return $form;
	}
}
