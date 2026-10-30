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
	private function make_field_stub( array $options, $primary = false ) {
		return new class( $options, $primary ) {
			private $options;
			private $primary;
			public function __construct( $options, $primary ) {
				$this->options = $options;
				$this->primary = $primary;
			}
			public function is_primary() { return $this->primary; }
			public function get_meta( $key ) {
				if ( 'dropdown_options' === $key ) {
					return $this->options;
				}
				return array();
			}
		};
	}

	/**
	 * Create a user with a display name written straight to the database, past
	 * the filters WordPress runs when a user is saved.
	 */
	private function user_with_stored_display_name( $display_name ) {
		global $wpdb;

		$user_id = $this->factory()->user->create();
		$wpdb->update( $wpdb->users, array( 'display_name' => $display_name ), array( 'ID' => $user_id ) );
		clean_user_cache( $user_id );

		return $user_id;
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

	// ---- Escaping ----

	public function test_dropdown_escapes_a_primary_field_value() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-dropdown.php';
		$field_type = new WPUM_Field_Dropdown();

		$result = $field_type->get_formatted_output( $this->make_field_stub( array(), true ), '<img src=x onerror=alert(1)>' );

		$this->assertEquals( '&lt;img src=x onerror=alert(1)&gt;', $result );
	}

	public function test_dropdown_escapes_an_option_label() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-dropdown.php';
		$field_type = new WPUM_Field_Dropdown();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => '<b>Apple</b>' ),
		) );

		$this->assertEquals( '&lt;b&gt;Apple&lt;/b&gt;', $field_type->get_formatted_output( $field_stub, 'a' ) );
	}

	public function test_dropdown_escapes_a_value_with_no_matching_option() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-dropdown.php';
		$field_type = new WPUM_Field_Dropdown();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => 'Apple' ),
		) );

		$this->assertEquals( '&lt;b&gt;Pear&lt;/b&gt;', $field_type->get_formatted_output( $field_stub, '<b>Pear</b>' ) );
	}

	public function test_dropdown_does_not_encode_a_stored_entity_twice() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-dropdown.php';
		$field_type = new WPUM_Field_Dropdown();

		$result = $field_type->get_formatted_output( $this->make_field_stub( array(), true ), 'Tom &amp; Jerry' );

		$this->assertEquals( 'Tom &amp; Jerry', $result );
	}

	public function test_radio_escapes_a_primary_field_value() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-radio.php';
		$field_type = new WPUM_Field_Radio();

		$result = $field_type->get_formatted_output( $this->make_field_stub( array(), true ), '<img src=x onerror=alert(1)>' );

		$this->assertEquals( '&lt;img src=x onerror=alert(1)&gt;', $result );
	}

	public function test_radio_escapes_an_option_label() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-radio.php';
		$field_type = new WPUM_Field_Radio();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => '<b>Apple</b>' ),
		) );

		$this->assertEquals( '&lt;b&gt;Apple&lt;/b&gt;', $field_type->get_formatted_output( $field_stub, 'a' ) );
	}

	public function test_multicheckbox_escapes_option_labels() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multicheckbox.php';
		$field_type = new WPUM_Field_Multicheckbox();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => '<b>Apple</b>' ),
		) );

		$this->assertEquals( '&lt;b&gt;Apple&lt;/b&gt;', $field_type->get_formatted_output( $field_stub, array( 'a' ) ) );
	}

	public function test_multiselect_escapes_option_labels() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-multiselect.php';
		$field_type = new WPUM_Field_Multiselect();
		$field_stub = $this->make_field_stub( array(
			array( 'value' => 'a', 'label' => '<b>Apple</b>' ),
		) );

		$this->assertEquals( '&lt;b&gt;Apple&lt;/b&gt;', $field_type->get_formatted_output( $field_stub, array( 'a' ) ) );
	}

	public function test_user_field_escapes_display_names() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-user.php';
		$field_type = new WPUM_Field_User();
		$user_id    = $this->user_with_stored_display_name( '<img src=x onerror=alert(1)>' );

		$result = $field_type->get_formatted_output( $this->make_field_stub( array() ), $user_id );

		$this->assertEquals( '&lt;img src=x onerror=alert(1)&gt;', $result );
	}

	public function test_userrole_field_escapes_role_names() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-userrole.php';
		$field_type = new WPUM_Field_Userrole();
		add_role( 'wpum_escaped_role', '<b>Role</b>' );

		$result = $field_type->get_formatted_output( $this->make_field_stub( array() ), 'wpum_escaped_role' );
		remove_role( 'wpum_escaped_role' );

		$this->assertEquals( '&lt;b&gt;Role&lt;/b&gt;', $result );
	}

	public function test_taxonomy_field_escapes_term_names() {
		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-taxonomy.php';
		$field_type = new WPUM_Field_Taxonomy();
		$field_stub = new class() {
			public function get_meta( $key ) {
				return 'category';
			}
		};

		wp_set_current_user( $this->factory()->user->create() );
		add_filter( 'wp_get_object_terms', function () {
			return array( (object) array( 'name' => '<b>Term</b>' ) );
		} );

		$this->assertEquals( '&lt;b&gt;Term&lt;/b&gt;', $field_type->get_formatted_output( $field_stub, 'unused' ) );
	}

	public function test_display_name_field_value_is_escaped_for_the_profile() {
		global $wpdb;

		$field_id = (int) $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}wpum_fields WHERE type = 'user_displayname' LIMIT 1" );
		$user_id  = $this->user_with_stored_display_name( '<img src=x onerror=alert(1)>' );

		$field = new WPUM_Field( $field_id );
		$field->set_user_meta( $user_id );

		$this->assertEquals( '&lt;img src=x onerror=alert(1)&gt;', $field->get_value() );
	}
}
