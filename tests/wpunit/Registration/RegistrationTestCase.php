<?php
/**
 * Base test case for WPUM registration tests.
 *
 * Provides shared setup: bootstraps WPUM, creates custom tables,
 * installs default fields, and provides helper methods.
 */

abstract class RegistrationTestCase extends \Codeception\TestCase\WPTestCase {

	/**
	 * @var \WPUM_Registration_Form
	 */
	protected $default_form;

	/**
	 * @var int
	 */
	protected $default_form_id;

	public function _setUp() {
		parent::_setUp();

		// Ensure WPUM is loaded.
		if ( ! function_exists( 'WPUM' ) ) {
			$this->markTestSkipped( 'WPUM plugin is not active.' );
		}

		// Ensure custom tables exist.
		$this->ensure_tables();

		// Ensure default fields and registration form exist.
		$this->ensure_default_data();

		// Enable registration.
		update_option( 'users_can_register', true );

		// Load the form class.
		if ( ! class_exists( 'WPUM_Form' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/class-wpum-form.php';
		}
		if ( ! class_exists( 'WPUM_Form_Registration' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-registration.php';
		}
	}

	public function _tearDown() {
		// Reset the singleton so each test gets a fresh form instance.
		if ( class_exists( 'WPUM_Form_Registration' ) ) {
			$ref = new \ReflectionClass( 'WPUM_Form_Registration' );
			$prop = $ref->getProperty( '_instance' );
			$prop->setAccessible( true );
			$prop->setValue( null, null );
		}

		parent::_tearDown();
	}

	/**
	 * Ensure all WPUM custom tables exist.
	 */
	protected function ensure_tables() {
		$tables = array(
			new \WPUM_DB_Table_Fields(),
			new \WPUM_DB_Table_Field_Meta(),
			new \WPUM_DB_Table_Fields_Groups(),
			new \WPUM_DB_Table_Registration_Forms(),
			new \WPUM_DB_Table_Registration_Forms_Meta(),
			new \WPUM_DB_Table_Search_Fields(),
		);

		foreach ( $tables as $table ) {
			if ( ! $table->exists() ) {
				$table->create();
			}
		}
	}

	/**
	 * Ensure default fields and registration form are installed.
	 */
	protected function ensure_default_data() {
		$forms = WPUM()->registration_forms->get_forms();

		if ( empty( $forms ) ) {
			// Install default field group, fields, and registration form.
			wpum_install_default_field_group();
			$fields = wpum_install_fields();
			wpum_install_registration_form( $fields );
			// Re-fetch forms.
			$forms = WPUM()->registration_forms->get_forms();
		}

		if ( ! empty( $forms ) ) {
			$this->default_form    = $forms[0];
			$this->default_form_id = $this->default_form->get_ID();
		}
	}

	/**
	 * Simulate a registration form submission.
	 *
	 * @param array $post_data The $_POST data to submit.
	 * @return int|false New user ID or false on failure.
	 */
	protected function submit_registration( array $post_data ) {
		// Set up nonce.
		$nonce = wp_create_nonce( 'verify_registration_form' );

		// Field types read from $_POST[$key] directly (flat), not nested.
		$fields = isset( $post_data['register'] ) ? $post_data['register'] : array();

		$_POST = array_merge(
			array(
				'wpum_form'           => 'registration',
				'registration_nonce'  => $nonce,
				'submit_registration' => 'Register',
			),
			$fields
		);

		$form = \WPUM_Form_Registration::instance();

		$result = $form->submit_handler();

		return $result;
	}

	/**
	 * Build standard registration post data.
	 *
	 * @param array $overrides Override specific fields.
	 * @return array
	 */
	protected function get_valid_registration_data( array $overrides = array() ) {
		$register_overrides = isset( $overrides['register'] ) ? $overrides['register'] : array();
		unset( $overrides['register'] );

		$defaults = array(
			'register' => array_merge(
				array(
					'user_email'    => 'testuser_' . wp_rand() . '@example.com',
					'user_password' => 'StrongP@ssw0rd!123',
					'robo'          => '', // Honeypot must be empty.
					'privacy'       => '1', // Accept privacy policy.
				),
				$register_overrides
			),
		);

		return array_merge( $defaults, $overrides );
	}

	/**
	 * Get the registration form field keys from the default form.
	 *
	 * @return array
	 */
	protected function get_form_field_keys() {
		$form = \WPUM_Form_Registration::instance();

		$ref = new \ReflectionMethod( $form, 'get_registration_fields' );
		$ref->setAccessible( true );

		return array_keys( $ref->invoke( $form ) );
	}
}
