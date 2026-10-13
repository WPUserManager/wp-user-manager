<?php
/**
 * Tests for repeater fields nested inside a repeater (wpum-custom-fields#80).
 *
 * Structure used throughout:
 *
 *   wpum_jobs (repeater)
 *     wpum_company (text)
 *     wpum_roles (repeater)
 *       wpum_role_title (text)
 *       wpum_role_cv (file)
 */

require_once __DIR__ . '/FieldsTestCase.php';

class NestedRepeaterTest extends FieldsTestCase {

	/**
	 * @var int
	 */
	protected $group_id;

	/**
	 * @var \WPUM_Field
	 */
	protected $outer;

	/**
	 * @var \WPUM_Field
	 */
	protected $inner;

	/**
	 * @var \WPUM_Field
	 */
	protected $company;

	/**
	 * @var \WPUM_Field
	 */
	protected $role_title;

	/**
	 * @var array
	 */
	protected $files_backup;

	public function _setUp() {
		parent::_setUp();

		$this->files_backup = $_FILES;

		$this->group_id = $this->groups_db->insert( array(
			'name' => 'Nested Repeater Group ' . wp_generate_password( 6, false ),
		) );

		$this->outer      = $this->create_field( 'repeater', 'Jobs', 'wpum_jobs' );
		$this->company    = $this->create_field( 'text', 'Company', 'wpum_company', $this->outer );
		$this->inner      = $this->create_field( 'repeater', 'Roles', 'wpum_roles', $this->outer );
		$this->role_title = $this->create_field( 'text', 'Role title', 'wpum_role_title', $this->inner );
		$this->create_field( 'file', 'Role CV', 'wpum_role_cv', $this->inner );

		require_once WPUM_PLUGIN_DIR . 'includes/fields/types/class-wpum-field-repeater.php';
	}

	public function _tearDown() {
		$_FILES = $this->files_backup;

		parent::_tearDown();
	}

	/**
	 * Create a field, optionally as a sub field of a repeater.
	 */
	protected function create_field( $type, $name, $meta_key, $parent = null ) {
		$field_id = $this->fields_db->insert( array(
			'group_id'    => $this->group_id,
			'type'        => $type,
			'name'        => $name,
			'description' => '',
			'field_order' => 0,
		) );

		$field = new \WPUM_Field( $field_id );
		$field->add_meta( 'user_meta_key', $meta_key );
		$field->add_meta( 'visibility', 'public' );
		$field->add_meta( 'editing', 'public' );

		if ( $parent ) {
			$field->add_meta( 'parent_id', $parent->get_ID() );
		}

		return new \WPUM_Field( $field_id );
	}

	/**
	 * Rows as posted by the browser once the repeater JS has named the inputs.
	 */
	protected function nested_rows() {
		return array(
			array(
				'wpum_company' => 'Acme',
				'wpum_roles'   => array(
					array( 'wpum_role_title' => 'Engineer' ),
					array( 'wpum_role_title' => 'Lead' ),
				),
			),
			array(
				'wpum_company' => 'Globex',
				'wpum_roles'   => array(
					array( 'wpum_role_title' => 'Manager' ),
				),
			),
		);
	}

	protected function get_posted( $key, $field ) {
		$type = new \WPUM_Field_Repeater();

		return $type->get_posted_field( $key, $field );
	}

	protected function render_repeater( \WPUM_Field $field, $value ) {
		ob_start();
		WPUM()->templates
			->set_template_data( array(
				'id'       => $field->get_ID(),
				'key'      => $field->get_key(),
				'label'    => $field->get_name(),
				'type'     => 'repeater',
				'required' => false,
				'value'    => $value,
			) )
			->get_template_part( 'form-fields/complex', 'field' );

		return ob_get_clean();
	}

	public function test_nested_repeater_is_a_parent_field_type() {
		$this->assertContains( 'repeater', wpum_get_registered_parent_field_types() );
		$this->assertEquals( $this->outer->get_ID(), $this->inner->get_parent_ID() );
	}

	public function test_posted_nested_rows_keep_their_shape() {
		$_POST['wpum_jobs'] = $this->nested_rows();

		$posted = $this->get_posted( 'wpum_jobs', array( 'id' => $this->outer->get_ID() ) );

		$this->assertSame( $this->nested_rows(), $posted );
	}

	public function test_posted_nested_values_are_sanitised_at_every_level() {
		$_POST['wpum_jobs'] = array(
			array(
				'wpum_company' => ' <b>Acme</b> ',
				'wpum_roles'   => array(
					array( 'wpum_role_title' => '<script>alert(1)</script>Engineer\\\'s' ),
				),
			),
		);

		$posted = $this->get_posted( 'wpum_jobs', array( 'id' => $this->outer->get_ID() ) );

		$this->assertSame( 'Acme', $posted[0]['wpum_company'] );
		$this->assertSame( "Engineer's", $posted[0]['wpum_roles'][0]['wpum_role_title'] );
	}

	public function test_single_level_rows_still_post_as_before() {
		$_POST['wpum_jobs'] = array(
			array( 'wpum_company' => 'Acme' ),
			array( 'wpum_company' => 'Globex' ),
		);

		$posted = $this->get_posted( 'wpum_jobs', array( 'id' => $this->outer->get_ID() ) );

		$this->assertSame(
			array(
				array( 'wpum_company' => 'Acme' ),
				array( 'wpum_company' => 'Globex' ),
			),
			$posted
		);
	}

	/**
	 * A file input inside the nested repeater puts the nested repeater key into
	 * $_FILES. That must not wipe the nested rows that were posted in $_POST.
	 */
	public function test_empty_file_input_in_nested_repeater_keeps_nested_rows() {
		$_POST['wpum_jobs'] = $this->nested_rows();
		$_FILES['wpum_jobs'] = array();
		foreach ( array( 'name' => '', 'type' => '', 'tmp_name' => '', 'error' => 4, 'size' => 0 ) as $prop => $empty ) {
			$_FILES['wpum_jobs'][ $prop ] = array(
				array( 'wpum_roles' => array( array( 'wpum_role_cv' => $empty ), array( 'wpum_role_cv' => $empty ) ) ),
				array( 'wpum_roles' => array( array( 'wpum_role_cv' => $empty ) ) ),
			);
		}

		$posted = $this->get_posted( 'wpum_jobs', array( 'id' => $this->outer->get_ID() ) );

		$this->assertSame( 'Engineer', $posted[0]['wpum_roles'][0]['wpum_role_title'] );
		$this->assertSame( 'Lead', $posted[0]['wpum_roles'][1]['wpum_role_title'] );
		$this->assertSame( 'Manager', $posted[1]['wpum_roles'][0]['wpum_role_title'] );
	}

	public function test_nested_repeater_renders_its_rows_inside_each_parent_row() {
		$html = $this->render_repeater( $this->outer, $this->nested_rows() );

		// 2 saved outer rows + 1 clone row, each with its own nested repeater.
		$this->assertSame( 3, substr_count( $html, '<fieldset class="fieldset-wpum_roles">' ) );

		// The nested repeater is rendered by the complex template directly,
		// not wrapped in a second fieldset and label by form-registration-fields.
		$this->assertStringNotContainsString( 'for="wpum_roles"', $html );

		// Nested values are loaded into the nested rows.
		$this->assertStringContainsString( 'value="Engineer"', $html );
		$this->assertStringContainsString( 'value="Lead"', $html );
		$this->assertStringContainsString( 'value="Manager"', $html );

		// Server rendered names are relative to the nested repeater; the JS
		// prefixes them with the parent row path.
		$this->assertStringContainsString( 'name="wpum_roles[1][wpum_role_title]"', $html );
		$this->assertStringContainsString( 'name="wpum_jobs[1][wpum_company]"', $html );
	}

	public function test_single_level_value_still_renders() {
		$html = $this->render_repeater( $this->outer, array(
			array( 'wpum_company' => 'Acme' ),
			array( 'wpum_company' => 'Globex', '_type' => '_' ),
		) );

		$this->assertStringContainsString( 'value="Acme"', $html );
		$this->assertStringContainsString( 'value="Globex"', $html );
		// Old data has no nested rows: each outer row still gets an empty nested row.
		$this->assertSame( 3, substr_count( $html, '<fieldset class="fieldset-wpum_roles">' ) );
	}

	public function test_formatted_output_includes_nested_values() {
		$type = new \WPUM_Field_Repeater();
		$html = $type->get_formatted_output( $this->outer, $this->nested_rows() );

		$this->assertStringContainsString( '<strong>Company</strong>: Acme', $html );
		$this->assertStringContainsString( '<strong>Role title</strong>: Engineer', $html );
		$this->assertStringContainsString( '<strong>Role title</strong>: Manager', $html );
		$this->assertSame( 5, substr_count( $html, '<ul class="field_repeater_child">' ) );
	}

	/**
	 * Values saved through Carbon Fields come back with the nesting intact.
	 * This mirrors the complex field the custom fields addon registers.
	 */
	public function test_nested_rows_round_trip_through_carbon_fields() {
		\WPUM\Carbon_Fields\Container::make( 'user_meta', 'Nested repeater test ' . wp_generate_password( 6, false ) )
			->add_fields( array(
				\WPUM\Carbon_Fields\Field::make( 'complex', 'wpum_jobs', 'Jobs' )->add_fields( array(
					\WPUM\Carbon_Fields\Field::make( 'text', 'wpum_company', 'Company' ),
					\WPUM\Carbon_Fields\Field::make( 'complex', 'wpum_roles', 'Roles' )->add_fields( array(
						\WPUM\Carbon_Fields\Field::make( 'text', 'wpum_role_title', 'Role title' ),
					) ),
				) ),
			) );

		$user_id = self::factory()->user->create();

		\WPUM\carbon_set_user_meta( $user_id, 'wpum_jobs', $this->nested_rows() );
		$saved = \WPUM\carbon_get_user_meta( $user_id, 'wpum_jobs' );

		$this->assertCount( 2, $saved );
		$this->assertSame( 'Acme', $saved[0]['wpum_company'] );
		$this->assertCount( 2, $saved[0]['wpum_roles'] );
		$this->assertSame( 'Lead', $saved[0]['wpum_roles'][1]['wpum_role_title'] );
		$this->assertSame( 'Manager', $saved[1]['wpum_roles'][0]['wpum_role_title'] );

		// The saved value renders back into the form.
		$html = $this->render_repeater( $this->outer, $saved );
		$this->assertStringContainsString( 'value="Lead"', $html );
		// Carbon adds a "_type" key to every row; it must not render as a field.
		$this->assertStringNotContainsString( '[_type]', $html );
	}

	protected function validate_registration( $rows ) {
		if ( ! class_exists( 'WPUM_Form_Registration' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-registration.php';
		}

		$form   = \WPUM_Form_Registration::instance();
		$fields = array(
			'register' => array(
				'wpum_jobs' => array(
					'id'       => $this->outer->get_ID(),
					'label'    => 'Jobs',
					'type'     => 'repeater',
					'required' => false,
					'value'    => $rows,
				),
			),
		);

		return $form->validate_repeater( true, $fields, array(), 'registration' );
	}

	public function test_registration_validation_passes_valid_nested_rows() {
		$this->inner->update_meta( 'min_rows', 1 );
		$this->inner->update_meta( 'max_rows', 2 );

		$this->assertTrue( $this->validate_registration( $this->nested_rows() ) );
	}

	public function test_registration_validation_enforces_nested_min_rows() {
		$this->inner->update_meta( 'min_rows', 2 );

		$result = $this->validate_registration( $this->nested_rows() );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'Roles requires at least 2 rows', $result->get_error_message() );
	}

	public function test_registration_validation_enforces_nested_max_rows() {
		$this->inner->update_meta( 'max_rows', 1 );

		$result = $this->validate_registration( $this->nested_rows() );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'Roles accepts maximum 1 rows', $result->get_error_message() );
	}

	public function test_registration_validation_enforces_required_nested_repeater() {
		$this->inner->update_meta( 'required', true );

		$rows = $this->nested_rows();

		$rows[1]['wpum_roles'][0]['wpum_role_title'] = '';

		$result = $this->validate_registration( $rows );

		$this->assertWPError( $result );
		$this->assertStringContainsString( 'Please fill out Roles data', $result->get_error_message() );
	}

	public function test_registration_validation_of_single_level_rows_is_unchanged() {
		$this->outer->update_meta( 'min_rows', 2 );

		$result = $this->validate_registration( array( array( 'wpum_company' => 'Acme' ) ) );
		$this->assertWPError( $result );
		$this->assertStringContainsString( 'Jobs requires at least 2 rows', $result->get_error_message() );

		$this->assertTrue( $this->validate_registration( array(
			array( 'wpum_company' => 'Acme' ),
			array( 'wpum_company' => 'Globex' ),
		) ) );
	}
}
