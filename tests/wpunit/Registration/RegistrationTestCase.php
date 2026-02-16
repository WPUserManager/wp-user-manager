<?php
/**
 * Base test case for WPUM registration tests.
 *
 * Provides shared setup: bootstraps WPUM, creates custom tables,
 * installs default fields, and provides helper methods.
 */

namespace WPUM\Tests\Registration;

use Codeception\TestCase\WPTestCase;

abstract class RegistrationTestCase extends WPTestCase {

	/**
	 * @var \WPUM_Registration_Form
	 */
	protected $default_form;

	/**
	 * @var int
	 */
	protected $default_form_id;

	public function set_up() {
		parent::set_up();

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

	public function tear_down() {
		// Reset the singleton so each test gets a fresh form instance.
		$ref = new \ReflectionClass( 'WPUM_Form_Registration' );
		$prop = $ref->getProperty( '_instance' );
		$prop->setAccessible( true );
		$prop->setValue( null, null );

		parent::tear_down();
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
			// Install default field group + fields.
			wpum_install_fields();
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

		$_POST = array_merge(
			array(
				'wpum_form'          => 'registration',
				'registration_nonce' => $nonce,
				'submit_registration' => 'Register',
			),
			$post_data
		);

		$form = \WPUM_Form_Registration::instance();

		return $form->submit_handler();
	}

	/**
	 * Build standard registration post data.
	 *
	 * @param array $overrides Override specific fields.
	 * @return array
	 */
	protected function get_valid_registration_data( array $overrides = array() ) {
		$defaults = array(
			'register' => array_merge(
				array(
					'user_email'    => 'testuser_' . wp_rand() . '@example.com',
					'user_password' => 'StrongP@ssw0rd!123',
					'username'      => 'testuser_' . wp_rand(),
					'robo'          => '', // Honeypot must be empty.
				),
				isset( $overrides['register'] ) ? $overrides['register'] : array()
			),
		);

		unset( $overrides['register'] );

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
