<?php
/**
 * Tests for the US States field type (wpum-custom-fields#55).
 */

require_once __DIR__ . '/FieldsTestCase.php';

class StatesFieldTest extends FieldsTestCase {

	/**
	 * @var \WPUM_Field_States
	 */
	protected $field_type;

	public function _setUp() {
		parent::_setUp();

		$this->field_type = new WPUM_Field_States();
	}

	/**
	 * Build a minimal field stub.
	 *
	 * @param string $type
	 * @param array  $meta
	 *
	 * @return object
	 */
	private function make_field_stub( $type = 'states', array $meta = array() ) {
		return new class( $type, $meta ) {
			private $type;
			private $meta;
			public function __construct( $type, $meta ) {
				$this->type = $type;
				$this->meta = $meta;
			}
			public function get_type() {
				return $this->type;
			}
			public function get_meta( $key ) {
				return isset( $this->meta[ $key ] ) ? $this->meta[ $key ] : '';
			}
		};
	}

	/**
	 * Create a real states field in the primary group.
	 *
	 * @param array $meta
	 *
	 * @return \WPUM_Field
	 */
	private function create_states_field( array $meta = array() ) {
		$groups   = $this->groups_db->get_groups( array( 'primary' => true ) );
		$group_id = ! empty( $groups ) ? $groups[0]->get_ID() : $this->groups_db->insert( array(
			'name'       => 'Test Group',
			'is_primary' => 1,
		) );

		$field_id = $this->fields_db->insert( array(
			'group_id'    => $group_id,
			'type'        => 'states',
			'name'        => 'State',
			'description' => '',
			'field_order' => 99,
		) );

		foreach ( $meta as $key => $value ) {
			$this->field_meta_db->add_meta( $field_id, $key, $value );
		}

		return new WPUM_Field( $field_id );
	}

	// ---- Registration ----

	public function test_states_field_type_is_loaded() {
		$this->assertTrue( class_exists( 'WPUM_Field_States' ) );
		$this->assertArrayHasKey( 'states', WPUM()->field_types->get_registered_field_types_names() );
	}

	public function test_states_field_type_is_registered_in_advanced_group() {
		$fields = $this->field_type->register_field_type( array( 'advanced' => array( 'fields' => array() ) ) );

		$this->assertCount( 1, $fields['advanced']['fields'] );
		$registered = $fields['advanced']['fields'][0];
		$this->assertEquals( 'states', $registered['type'] );
		$this->assertEquals( '2.6.0', $registered['min_addon_version'] );
		$this->assertArrayHasKey( 'allow_multiple', $registered['settings']['general'] );
	}

	public function test_template_is_states() {
		$this->assertEquals( 'states', $this->field_type->template() );
		$this->assertFileExists( WPUM_PLUGIN_DIR . 'templates/form-fields/states-field.php' );
	}

	public function test_data_keys_include_allow_multiple() {
		$this->assertContains( 'allow_multiple', $this->field_type->get_data_keys() );
	}

	// ---- State list ----

	public function test_state_list_has_50_states_and_dc() {
		$states = wpum_get_us_states();

		$this->assertCount( 51, $states );
		$this->assertEquals( 'California', $states['CA'] );
		$this->assertEquals( 'District of Columbia', $states['DC'] );
		$this->assertEquals( 'Wyoming', $states['WY'] );

		foreach ( $states as $code => $name ) {
			$this->assertMatchesRegularExpression( '/^[A-Z]{2}$/', $code );
			$this->assertNotEmpty( $name );
		}
	}

	public function test_state_list_is_filterable() {
		$add_pr = function ( $states ) {
			$states['PR'] = 'Puerto Rico';
			return $states;
		};
		add_filter( 'wpum_us_states', $add_pr );

		$states = wpum_get_us_states();

		remove_filter( 'wpum_us_states', $add_pr );

		$this->assertEquals( 'Puerto Rico', $states['PR'] );
	}

	// ---- Options ----

	public function test_single_select_options_start_with_empty_placeholder() {
		$options = $this->field_type->get_field_options( array(), $this->make_field_stub() );

		$this->assertSame( '', array_key_first( $options ) );
		$this->assertEquals( 'Select a state', $options[''] );
		$this->assertCount( 52, $options );
		$this->assertEquals( 'New York', $options['NY'] );
	}

	public function test_single_select_uses_field_placeholder() {
		$options = $this->field_type->get_field_options( array(), $this->make_field_stub( 'states', array( 'placeholder' => 'Your state' ) ) );

		$this->assertEquals( 'Your state', $options[''] );
	}

	public function test_multiselect_options_have_no_placeholder() {
		$options = $this->field_type->get_field_options( array(), $this->make_field_stub( 'states', array( 'allow_multiple' => '1' ) ) );

		$this->assertArrayNotHasKey( '', $options );
		$this->assertCount( 51, $options );
	}

	public function test_options_for_other_field_types_are_untouched() {
		$options = $this->field_type->get_field_options( array( 'a' => 'Apple' ), $this->make_field_stub( 'dropdown' ) );

		$this->assertEquals( array( 'a' => 'Apple' ), $options );
	}

	public function test_form_options_filter_supplies_states_for_real_field() {
		$field = $this->create_states_field();

		$options = apply_filters( 'wpum_form_custom_field_dropdown_options', array(), $field );

		$this->assertArrayHasKey( 'TX', $options );
		$this->assertArrayHasKey( '', $options );
	}

	// ---- Posted value validation ----

	public function test_posted_valid_state_is_kept() {
		$_POST['state'] = 'CA';

		$this->assertEquals( 'CA', $this->field_type->get_posted_field( 'state', array() ) );
	}

	public function test_posted_unknown_state_is_rejected() {
		$_POST['state'] = 'XX';

		$this->assertSame( '', $this->field_type->get_posted_field( 'state', array() ) );
	}

	public function test_posted_state_name_instead_of_code_is_rejected() {
		$_POST['state'] = 'California';

		$this->assertSame( '', $this->field_type->get_posted_field( 'state', array() ) );
	}

	public function test_posted_markup_is_rejected() {
		$_POST['state'] = '<script>alert(1)</script>';

		$this->assertSame( '', $this->field_type->get_posted_field( 'state', array() ) );
	}

	public function test_posted_multiple_states_drop_unknown_values() {
		$_POST['state'] = array( 'CA', 'XX', 'NY', '' );

		$this->assertEquals( array( 'CA', 'NY' ), $this->field_type->get_posted_field( 'state', array() ) );
	}

	public function test_missing_posted_value_is_empty() {
		$this->assertSame( '', $this->field_type->get_posted_field( 'state', array() ) );
	}

	// ---- Output ----

	public function test_formatted_output_shows_state_name() {
		$this->assertEquals( 'California', $this->field_type->get_formatted_output( $this->make_field_stub(), 'CA' ) );
	}

	public function test_formatted_output_joins_multiple_states() {
		$this->assertEquals( 'California, New York', $this->field_type->get_formatted_output( $this->make_field_stub(), array( 'CA', 'NY' ) ) );
	}

	public function test_formatted_output_ignores_unknown_and_empty_values() {
		$this->assertSame( '', $this->field_type->get_formatted_output( $this->make_field_stub(), 'XX' ) );
		$this->assertSame( '', $this->field_type->get_formatted_output( $this->make_field_stub(), null ) );
		$this->assertSame( '', $this->field_type->get_formatted_output( $this->make_field_stub(), '' ) );
	}

	public function test_formatted_output_escapes_filtered_labels() {
		$evil = function ( $states ) {
			$states['CA'] = '<b>Cali</b>';
			return $states;
		};
		add_filter( 'wpum_us_states', $evil );

		$output = $this->field_type->get_formatted_output( $this->make_field_stub(), 'CA' );

		remove_filter( 'wpum_us_states', $evil );

		$this->assertEquals( '&lt;b&gt;Cali&lt;/b&gt;', $output );
	}

	public function test_profile_value_shows_state_name() {
		$field   = $this->create_states_field( array( 'user_meta_key' => 'us_state' ) );
		$user_id = self::factory()->user->create();
		update_user_meta( $user_id, 'us_state', 'TX' );

		$field->set_user_meta( $user_id );

		$this->assertEquals( 'Texas', $field->get_value() );
	}
}
