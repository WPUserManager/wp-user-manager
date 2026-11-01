<?php
/**
 * done() signs in, and the step comes from the request, so it only runs after submit_handler()
 * has checked the login in the same request.
 */

require_once __DIR__ . '/LoginTestCase.php';

class LoginStepsTest extends LoginTestCase {

	const PASSWORD = 'correct-horse-battery';

	protected $user;

	protected $sign_ins = 0;

	public function _setUp() {
		parent::_setUp();

		$this->user = get_userdata( $this->factory()->user->create( array( 'user_pass' => self::PASSWORD ) ) );

		add_filter( 'send_auth_cookies', '__return_false' );
		add_action( 'wp_login', function () {
			++$this->sign_ins;
		} );
		add_filter( 'wp_redirect', function ( $location ) {
			throw new \RuntimeException( $location );
		} );
	}

	public function _tearDown() {
		$_POST = array();

		parent::_tearDown();
	}

	/**
	 * Post the form at a step, as process() runs it on the wp hook.
	 *
	 * @param int    $step
	 * @param string $password
	 *
	 * @return \WPUM_Form_Login
	 */
	protected function post_at_step( $step, $password = self::PASSWORD ) {
		$_POST = array(
			'username'     => $this->user->user_login,
			'password'     => $password,
			'remember'     => '',
			'submit_login' => 'Login',
		);

		$form = \WPUM_Form_Login::instance();

		$property = new ReflectionProperty( $form, 'step' );
		$property->setAccessible( true );
		$property->setValue( $form, $step );

		$form->process();

		return $form;
	}

	protected function form_errors( $form ) {
		$property = new ReflectionProperty( $form, 'errors' );
		$property->setAccessible( true );

		return $property->getValue( $form );
	}

	public function test_a_good_login_signs_in() {
		$this->post_at_step( 0 );

		$this->assertSame( 1, $this->sign_ins );
	}

	public function test_a_wrong_password_does_not_sign_in() {
		$form = $this->post_at_step( 0, 'not-the-password' );

		$this->assertSame( 0, $this->sign_ins );
		$this->assertNotEmpty( $this->form_errors( $form ) );
	}

	public function test_the_last_step_does_not_sign_in_on_its_own() {
		$form = $this->post_at_step( 1 );

		$this->assertSame( 0, $this->sign_ins );
		$this->assertSame( 0, get_current_user_id() );
		$this->assertSame( array( 'Please enter your login details.' ), $this->form_errors( $form ) );
	}

	public function test_the_last_step_goes_back_to_the_form() {
		$form = $this->post_at_step( 1 );

		$this->assertSame( 0, $form->get_step() );
	}

	public function test_a_validator_applies_at_every_step() {
		add_filter( 'submit_wpum_form_validate_fields', function () {
			return new WP_Error( 'refused', 'Refused.' );
		} );

		foreach ( array( 0, 1 ) as $step ) {
			$this->reset_singleton( 'WPUM_Form_Login', 'instance' );

			$this->post_at_step( $step );

			$this->assertSame( 0, $this->sign_ins, "Step $step signed in." );
		}
	}
}
