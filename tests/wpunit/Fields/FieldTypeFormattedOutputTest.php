<?php
/**
 * Tests for field type get_formatted_output() methods.
 *
 * Regression tests for #178: non-array $value passed to get_formatted_output()
 * should not throw a PHP warning.
 */

require_once __DIR__ . '/FieldsTestCase.php';

class FieldTypeFormattedOutputTest extends FieldsTestCase {

	/**
	 * Build a minimal field stub with dropdown_options meta.
	 */
	private function make_field_stub( array $options ) {
		return new class( $options ) {
			private $options;
			public function __construct( $options ) { $this->options = $options; }
			public function get_meta( $key ) {
				if ( 'dropdown_options' === $key ) {
					return $this->options;
				}
				return array();
			}
		};
	}

	// ---- Multicheckbox ----

	public function test_multicheckbox_with_array_value_returns_labels() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multicheckbox.php';
		$field_type = new WPUM_Field_Multicheckbox();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'opt1', 'label' => 'Option 1' ),
			array( 'value' => 'opt2', 'label' => 'Option 2' ),
		) );

		$result = $field_type->get_formatted_output( $field_stub, array( 'opt1', 'opt2' ) );
		$this->assertEquals( 'Option 1, Option 2', $result );
	}

	public function test_multicheckbox_with_null_value_returns_empty_string() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multicheckbox.php';
		$field_type = new WPUM_Field_Multicheckbox();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'opt1', 'label' => 'Option 1' ),
		) );

		$result = $field_type->get_formatted_output( $field_stub, null );
		$this->assertEquals( '', $result );
	}

	public function test_multicheckbox_with_empty_string_value_returns_empty_string() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multicheckbox.php';
		$field_type = new WPUM_Field_Multicheckbox();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'opt1', 'label' => 'Option 1' ),
		) );

		$result = $field_type->get_formatted_output( $field_stub, '' );
		$this->assertEquals( '', $result );
	}

	// ---- Multiselect ----

	public function test_multiselect_with_array_value_returns_labels() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multiselect.php';
		$field_type = new WPUM_Field_Multiselect();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => 'Apple' ),
			array( 'value' => 'b', 'label' => 'Banana' ),
		) );

		$result = $field_type->get_formatted_output( $field_stub, array( 'a', 'b' ) );
		$this->assertEquals( 'Apple, Banana', $result );
	}

	public function test_multiselect_with_null_value_returns_empty_string() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multiselect.php';
		$field_type = new WPUM_Field_Multiselect();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => 'Apple' ),
		) );

		$result = $field_type->get_formatted_output( $field_stub, null );
		$this->assertEquals( '', $result );
	}

	public function test_multiselect_with_empty_string_value_returns_empty_string() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multiselect.php';
		$field_type = new WPUM_Field_Multiselect();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => 'Apple' ),
		) );

		$result = $field_type->get_formatted_output( $field_stub, '' );
		$this->assertEquals( '', $result );
	}
}
