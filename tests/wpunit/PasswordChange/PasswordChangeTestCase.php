<?php
/**
 * Base test case for WPUM password change form tests.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

abstract class PasswordChangeTestCase extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $test_user_id;

	public function _setUp() {
		parent::_setUp();

		// Load the password change form class.
		if ( ! class_exists( 'WPUM_Form_Password' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-password.php';
		}
	}

	public function _tearDown() {
		// Reset singleton.
		$this->reset_singleton( 'WPUM_Form_Password', 'instance' );

		// Remove filters the constructor adds.
		remove_all_filters( 'submit_wpum_form_validate_fields' );
		remove_all_actions( 'wp' );

		// Log out.
		wp_set_current_user( 0 );

		parent::_tearDown();
	}

	/**
	 * Create a test user and log them in.
	 *
	 * @return int User ID.
	 */
	protected function create_and_login_user() {
		$user_id = $this->factory()->user->create( array(
			'user_login' => 'pwchange_user_' . wp_rand(),
			'user_pass'  => 'OldP@ssw0rd!',
			'user_email' => 'pwchange_' . wp_rand() . '@example.com',
			'role'       => 'subscriber',
		) );

		wp_set_current_user( $user_id );
		$this->test_user_id = $user_id;

		return $user_id;
	}
}
