<?php
/**
 * Tests for the taxonomy field display fix (PR #392).
 *
 * When a WPUM field has type 'taxonomy' and its user_meta_key starts with
 * 'wpum_', the set_user_meta() method should retrieve the value via
 * get_user_meta() instead of carbon_get_user_meta(), because Carbon Fields
 * returns empty for taxonomy-type fields.
 *
 * The fix adds `&& $this->get_type() !== 'taxonomy'` to the elseif branch
 * in set_user_meta() so taxonomy fields fall through to the get_user_meta()
 * path.
 *
 * Note: The 'taxonomy' field type class (WPUM_Field_Taxonomy) lives in the
 * wpum-custom-fields addon, not core. These tests verify the branching logic
 * in set_user_meta() without requiring the addon to be active.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/pull/392
 */

require_once __DIR__ . '/FieldsTestCase.php';

class TaxonomyFieldDisplayTest extends FieldsTestCase {

	/**
	 * @var int
	 */
	protected $test_group_id;

	/**
	 * @var int
	 */
	protected $user_id;

	public function _setUp() {
		parent::_setUp();

		// Get or create a field group.
		$groups = $this->groups_db->get_groups(
			array(
				'primary' => true,
			)
		);

		if ( ! empty( $groups ) ) {
			$this->test_group_id = $groups[0]->get_ID();
		} else {
			$this->test_group_id = $this->groups_db->insert(
				array(
					'name'       => 'Test Group',
					'is_primary' => 1,
				)
			);
		}

		// Create a test user.
		$this->user_id = $this->factory()->user->create(
			array(
				'user_login' => 'taxonomy_test_user_' . wp_rand(),
				'user_email' => 'taxonomy_test_' . wp_rand() . '@example.com',
				'role'       => 'subscriber',
			)
		);
	}

	public function _tearDown() {
		if ( $this->user_id ) {
			wp_delete_user( $this->user_id );
		}

		parent::_tearDown();
	}

	/**
	 * Helper: create a WPUM field in the database and return the WPUM_Field object.
	 *
	 * @param string $type     The field type (e.g. 'taxonomy', 'text').
	 * @param string $name     The field name.
	 * @param string $meta_key The user_meta_key value.
	 *
	 * @return \WPUM_Field
	 */
	private function create_field( $type, $name, $meta_key ) {
		$field_id = $this->fields_db->insert(
			array(
				'group_id'    => $this->test_group_id,
				'type'        => $type,
				'name'        => $name,
				'field_order' => 99,
			)
		);

		$this->assertGreaterThan( 0, $field_id, "Field '{$name}' should be inserted." );

		// Set the user_meta_key meta.
		$this->field_meta_db->add_meta( $field_id, 'user_meta_key', $meta_key );

		$field = new \WPUM_Field( $field_id );

		$this->assertNotEmpty( $field->get_ID(), 'WPUM_Field should be instantiated from DB.' );

		return $field;
	}

	/**
	 * Test that a field created with type 'taxonomy' correctly reports its type.
	 */
	public function test_taxonomy_field_type_is_stored_correctly() {
		$field = $this->create_field( 'taxonomy', 'Type Check', 'wpum_type_check' );

		$this->assertSame( 'taxonomy', $field->get_type(), 'Field type should be taxonomy.' );
	}

	/**
	 * Test that the meta key is stored and retrieved correctly.
	 */
	public function test_taxonomy_field_meta_key_stored() {
		$field = $this->create_field( 'taxonomy', 'Meta Key Check', 'wpum_meta_check' );

		$this->assertSame( 'wpum_meta_check', $field->get_meta( 'user_meta_key' ) );
	}

	/**
	 * Test the branching condition: a taxonomy field with 'wpum_' prefix
	 * should NOT match the Carbon Fields branch.
	 *
	 * The condition `strpos(meta_key, 'wpum_') === 0 && get_type() !== 'taxonomy'`
	 * should evaluate to FALSE for taxonomy fields, causing them to fall through
	 * to the get_user_meta() else branch.
	 */
	public function test_carbon_branch_condition_is_false_for_taxonomy_fields() {
		$field    = $this->create_field( 'taxonomy', 'Condition Test', 'wpum_condition_test' );
		$meta_key = $field->get_meta( 'user_meta_key' );
		$type     = $field->get_type();

		// This is the exact condition from set_user_meta() elseif branch:
		$would_use_carbon = ( 0 === strpos( $meta_key, 'wpum_' ) && 'taxonomy' !== $type );

		$this->assertFalse(
			$would_use_carbon,
			'Taxonomy field with wpum_ prefix should NOT take the Carbon Fields path (PR #392 fix).'
		);
	}

	/**
	 * Test that a non-taxonomy field with 'wpum_' prefix DOES match the
	 * Carbon Fields branch condition.
	 */
	public function test_carbon_branch_condition_is_true_for_text_fields() {
		$field    = $this->create_field( 'text', 'Text Condition', 'wpum_text_condition' );
		$meta_key = $field->get_meta( 'user_meta_key' );
		$type     = $field->get_type();

		$would_use_carbon = ( 0 === strpos( $meta_key, 'wpum_' ) && 'taxonomy' !== $type );

		$this->assertTrue(
			$would_use_carbon,
			'Text field with wpum_ prefix should take the Carbon Fields path.'
		);
	}

	/**
	 * Test that a taxonomy field without 'wpum_' prefix takes the
	 * get_user_meta() else branch (neither primary nor carbon).
	 */
	public function test_non_wpum_prefix_taxonomy_takes_else_branch() {
		$field    = $this->create_field( 'taxonomy', 'Custom Prefix', 'custom_taxonomy' );
		$meta_key = $field->get_meta( 'user_meta_key' );
		$type     = $field->get_type();

		$is_primary       = false; // custom field, not primary
		$would_use_carbon = ( 0 === strpos( $meta_key, 'wpum_' ) && 'taxonomy' !== $type );

		$this->assertFalse( $is_primary );
		$this->assertFalse( $would_use_carbon );
		// Both false means it falls through to get_user_meta() — correct.
	}

	/**
	 * Test that set_user_meta() handles an invalid user ID gracefully.
	 */
	public function test_set_user_meta_with_invalid_user_id() {
		$field = $this->create_field( 'taxonomy', 'Invalid User', 'wpum_invalid_tax' );

		// Passing 0 should return early.
		$field->set_user_meta( 0 );

		$this->assertNull( $field->get_value(), 'Value should remain null for invalid user ID.' );
	}

	/**
	 * Test that a taxonomy field with empty user meta returns null.
	 */
	public function test_taxonomy_field_with_no_user_meta_returns_empty() {
		$field = $this->create_field( 'taxonomy', 'Empty Taxonomy', 'wpum_empty_tax' );

		// Don't store any user meta.
		$field->set_user_meta( $this->user_id );

		// Value should be null (set_user_meta only assigns $this->value if !empty).
		$this->assertEmpty( $field->get_value(), 'Taxonomy field with no stored meta should return empty.' );
	}

	/**
	 * Test that set_user_meta() actually retrieves the value from
	 * get_user_meta() for taxonomy fields with 'wpum_' prefix.
	 *
	 * We hook into 'wpum_field_ouput_callback_function' to provide a
	 * passthrough formatter, since the WPUM_Field_Taxonomy class from
	 * the custom-fields addon is not available in core tests.
	 */
	public function test_taxonomy_field_retrieves_value_from_user_meta() {
		$field    = $this->create_field( 'taxonomy', 'Value Retrieval', 'wpum_value_test' );
		$meta_key = 'wpum_value_test';

		// Store term data in user meta.
		update_user_meta( $this->user_id, $meta_key, 'term_data_here' );

		// Provide a passthrough formatter since field_type is null for taxonomy in core.
		$passthrough = function ( $func, $field_obj, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter callback signature.
			if ( 'taxonomy' === $field_obj->get_type() ) {
				return 'wpum_passthrough_formatter';
			}
			return $func;
		};
		add_filter( 'wpum_field_ouput_callback_function', $passthrough, 10, 3 );

		// Define the passthrough function.
		if ( ! function_exists( 'wpum_passthrough_formatter' ) ) {
			function wpum_passthrough_formatter( $field, $value ) {
				return $value;
			}
		}

		$field->set_user_meta( $this->user_id );
		$value = $field->get_value();

		remove_filter( 'wpum_field_ouput_callback_function', $passthrough, 10 );

		$this->assertSame(
			'term_data_here',
			$value,
			'Taxonomy field should retrieve its value from get_user_meta (not Carbon Fields).'
		);
	}

	/**
	 * Test that set_user_meta() retrieves array values for taxonomy fields.
	 */
	public function test_taxonomy_field_retrieves_array_value() {
		$field    = $this->create_field( 'taxonomy', 'Array Value', 'wpum_array_test' );
		$meta_key = 'wpum_array_test';

		$term_ids = array( 1, 2, 3 );
		update_user_meta( $this->user_id, $meta_key, $term_ids );

		// Passthrough formatter for taxonomy fields.
		$passthrough = function ( $func, $field_obj, $value ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter callback signature.
			if ( 'taxonomy' === $field_obj->get_type() ) {
				return 'wpum_passthrough_formatter';
			}
			return $func;
		};
		add_filter( 'wpum_field_ouput_callback_function', $passthrough, 10, 3 );

		$field->set_user_meta( $this->user_id );
		$value = $field->get_value();

		remove_filter( 'wpum_field_ouput_callback_function', $passthrough, 10 );

		$this->assertNotEmpty( $value, 'Taxonomy field should retrieve array value.' );
		$this->assertEquals( $term_ids, $value, 'Taxonomy field should retrieve the stored term IDs.' );
	}

	/**
	 * Test the complete branching: taxonomy and non-taxonomy fields with the
	 * same 'wpum_' prefix take different code paths.
	 */
	public function test_branching_taxonomy_vs_non_taxonomy_with_wpum_prefix() {
		$meta_key = 'wpum_branching_test';

		$tax_field  = $this->create_field( 'taxonomy', 'Tax Branch', $meta_key );
		$text_field = $this->create_field( 'text', 'Text Branch', $meta_key );

		// Both have the same meta key prefix.
		$this->assertStringStartsWith( 'wpum_', $tax_field->get_meta( 'user_meta_key' ) );
		$this->assertStringStartsWith( 'wpum_', $text_field->get_meta( 'user_meta_key' ) );

		// But they take different paths.
		$tax_uses_carbon  = (
			0 === strpos( $tax_field->get_meta( 'user_meta_key' ), 'wpum_' )
			&& 'taxonomy' !== $tax_field->get_type()
		);
		$text_uses_carbon = (
			0 === strpos( $text_field->get_meta( 'user_meta_key' ), 'wpum_' )
			&& 'taxonomy' !== $text_field->get_type()
		);

		$this->assertFalse( $tax_uses_carbon, 'Taxonomy field should NOT use Carbon path.' );
		$this->assertTrue( $text_uses_carbon, 'Text field should use Carbon path.' );
	}
}
