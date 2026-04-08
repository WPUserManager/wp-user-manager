<?php
/**
 * Base test case for WPUM account form tests.
 *
 * Provides a test harness that exposes the protected update_account_values()
 * method from the WPUM_Form_Account trait.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Minimal class that uses the account trait so we can call update_account_values().
 */
class WPUM_Account_Test_Harness {

	use WPUM_Form_Account;

	/**
	 * Proxy so tests can call the protected method.
	 *
	 * @param \WP_User $user
	 * @param array    $values
	 * @param bool     $partial_form
	 *
	 * @return int|WP_Error
	 * @throws Exception
	 */
	public function do_update( $user, $values, $partial_form = false ) {
		return $this->update_account_values( $user, $values, $partial_form );
	}

	/**
	 * Stub required by the trait when display name is updated.
	 */
	protected function parse_displayname( $account, $value ) {
		return $value;
	}
}

abstract class AccountTestCase extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $test_user_id;

	/**
	 * @var WPUM_Account_Test_Harness
	 */
	protected $harness;

	/**
	 * @var string Uploads basedir for creating test files.
	 */
	protected $upload_dir;

	public function _setUp() {
		parent::_setUp();

		// Load the trait file.
		if ( ! trait_exists( 'WPUM_Form_Account' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/trait-wpum-account.php';
		}

		$this->harness    = new WPUM_Account_Test_Harness();
		$this->upload_dir = wp_upload_dir()['basedir'];
	}

	public function _tearDown() {
		$_POST    = array();
		$_REQUEST = array();

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
			'user_login' => 'account_user_' . wp_rand(),
			'user_pass'  => 'TestP@ss123!',
			'user_email' => 'account_' . wp_rand() . '@example.com',
			'role'       => 'subscriber',
		) );

		wp_set_current_user( $user_id );
		$this->test_user_id = $user_id;

		return $user_id;
	}

	/**
	 * Create a temporary file in the uploads directory.
	 *
	 * @param string $filename
	 *
	 * @return string Full path to the created file.
	 */
	protected function create_temp_upload( $filename = 'test-avatar.jpg' ) {
		$path = $this->upload_dir . '/' . $filename;
		file_put_contents( $path, 'test file content' );

		return $path;
	}

	/**
	 * Build a minimal $values array for update_account_values().
	 *
	 * @param array $overrides Keys to merge into $values['account'].
	 *
	 * @return array
	 */
	protected function build_values( $overrides = array() ) {
		$user = get_user_by( 'id', $this->test_user_id );

		return array(
			'account' => array_merge(
				array(
					'user_email' => $user->user_email,
				),
				$overrides
			),
		);
	}
}
