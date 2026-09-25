<?php
/**
 * Shared base test case for all WPUM test suites.
 *
 * Provides common setup: bootstraps WPUM, creates custom tables,
 * installs default fields, and loads the abstract form class.
 */

abstract class WPUMTestCase extends \Codeception\TestCase\WPTestCase {

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

		// Load the abstract form class.
		if ( ! class_exists( 'WPUM_Form' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/class-wpum-form.php';
		}
	}

	public function _tearDown() {
		// Clean up $_POST between tests.
		$_POST    = array();
		$_REQUEST = array();

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
			wpum_install_default_field_group();
			$fields = wpum_install_fields();
			wpum_install_registration_form( $fields );
		}
	}

	/**
	 * Reset a singleton form instance using reflection.
	 *
	 * @param string $class_name    The full class name.
	 * @param string $property_name The static property that holds the instance.
	 */
	protected function reset_singleton( $class_name, $property_name = 'instance' ) {
		if ( class_exists( $class_name ) ) {
			$ref  = new \ReflectionClass( $class_name );
			$prop = $ref->getProperty( $property_name );
			$prop->setAccessible( true );
			$prop->setValue( null, null );
		}
	}

	/**
	 * Get form errors from a form instance via reflection.
	 *
	 * @param object $form The form instance.
	 *
	 * @return array
	 */
	protected function get_form_errors( $form ) {
		$ref  = new \ReflectionClass( $form );
		$prop = $ref->getProperty( 'errors' );
		$prop->setAccessible( true );

		return $prop->getValue( $form );
	}

	/**
	 * Get the form step value via reflection.
	 *
	 * @param object $form The form instance.
	 *
	 * @return int
	 */
	protected function get_form_step( $form ) {
		return $form->get_step();
	}
}
