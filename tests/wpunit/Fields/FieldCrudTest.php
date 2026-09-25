<?php
/**
 * Tests for WPUM field CRUD operations.
 */

require_once __DIR__ . '/FieldsTestCase.php';

class FieldCrudTest extends FieldsTestCase {

	/**
	 * @var int
	 */
	protected $test_group_id;

	public function _setUp() {
		parent::_setUp();

		// Get the primary group ID for field tests.
		$groups = $this->groups_db->get_groups( array(
			'primary' => true,
		) );

		if ( ! empty( $groups ) ) {
			$this->test_group_id = $groups[0]->get_ID();
		} else {
			$this->test_group_id = $this->groups_db->insert( array(
				'name'       => 'Test Group',
				'is_primary' => 1,
			) );
		}
	}

	/**
	 * Test inserting a new field.
	 */
	public function test_create_field() {
		$field_id = $this->fields_db->insert( array(
			'group_id'    => $this->test_group_id,
			'type'        => 'text',
			'name'        => 'Test Custom Field',
			'description' => 'A test field.',
			'field_order' => 99,
		) );

		$this->assertGreaterThan( 0, $field_id, 'Field insert should return a positive ID.' );
	}

	/**
	 * Test retrieving a field by ID.
	 */
	public function test_get_field_by_id() {
		$field_id = $this->fields_db->insert( array(
			'group_id'    => $this->test_group_id,
			'type'        => 'text',
			'name'        => 'Retrieve Me',
			'description' => '',
			'field_order' => 99,
		) );

		$row = $this->fields_db->get( $field_id );

		$this->assertNotNull( $row, 'Should be able to retrieve the field by ID.' );
		$this->assertEquals( 'Retrieve Me', $row->name );
	}

	/**
	 * Test updating a field.
	 */
	public function test_update_field() {
		$field_id = $this->fields_db->insert( array(
			'group_id' => $this->test_group_id,
			'type'     => 'text',
			'name'     => 'Before Update',
		) );

		$updated = $this->fields_db->update( $field_id, array(
			'name' => 'After Update',
			'type' => 'email',
		) );

		$this->assertTrue( $updated, 'Field update should return true.' );

		$row = $this->fields_db->get( $field_id );
		$this->assertEquals( 'After Update', $row->name );
		$this->assertEquals( 'email', $row->type );
	}

	/**
	 * Test deleting a field.
	 */
	public function test_delete_field() {
		$field_id = $this->fields_db->insert( array(
			'group_id' => $this->test_group_id,
			'type'     => 'text',
			'name'     => 'Delete Me',
		) );

		$deleted = $this->fields_db->delete( $field_id );
		$this->assertTrue( $deleted, 'Field delete should return true.' );

		$row = $this->fields_db->get( $field_id );
		$this->assertNull( $row, 'Deleted field should not be found.' );
	}

	/**
	 * Test field meta CRUD operations.
	 */
	public function test_field_meta_crud() {
		$field_id = $this->fields_db->insert( array(
			'group_id' => $this->test_group_id,
			'type'     => 'text',
			'name'     => 'Meta Test Field',
		) );

		// Add meta.
		$meta_id = $this->field_meta_db->add_meta( $field_id, 'test_key', 'test_value' );
		$this->assertGreaterThan( 0, $meta_id, 'Meta insert should return a positive meta ID.' );

		// Get meta.
		$value = $this->field_meta_db->get_meta( $field_id, 'test_key', true );
		$this->assertEquals( 'test_value', $value, 'Meta value should match what was inserted.' );

		// Update meta.
		$this->field_meta_db->update_meta( $field_id, 'test_key', 'updated_value' );
		$updated_value = $this->field_meta_db->get_meta( $field_id, 'test_key', true );
		$this->assertEquals( 'updated_value', $updated_value, 'Meta value should be updated.' );

		// Delete meta.
		$this->field_meta_db->delete_meta( $field_id, 'test_key' );
		$deleted_value = $this->field_meta_db->get_meta( $field_id, 'test_key', true );
		$this->assertEmpty( $deleted_value, 'Meta value should be empty after deletion.' );
	}

	/**
	 * Test that default fields are installed after ensure_default_data.
	 */
	public function test_default_fields_installed() {
		$fields = WPUM()->fields->get_fields( array(
			'group_id' => $this->test_group_id,
			'orderby'  => 'field_order',
			'order'    => 'ASC',
		) );

		$this->assertNotEmpty( $fields, 'Default fields should be installed.' );

		// Check that at least user_email and user_password type fields exist.
		$types = array_map( function( $field ) {
			$primary_id = $field->get_primary_id();
			return $primary_id ? $primary_id : $field->get_type();
		}, $fields );

		$this->assertTrue(
			in_array( 'user_email', $types, true ),
			'Default fields should include user_email.'
		);
		$this->assertTrue(
			in_array( 'user_password', $types, true ),
			'Default fields should include user_password.'
		);
	}

	/**
	 * Test that the WPUM_Field object correctly identifies primary fields.
	 */
	public function test_primary_field_mapping() {
		// Retrieve fields from the default group.
		$fields = WPUM()->fields->get_fields( array(
			'group_id' => $this->test_group_id,
			'orderby'  => 'field_order',
			'order'    => 'ASC',
		) );

		$found_primary = false;
		foreach ( $fields as $field ) {
			if ( $field->is_primary() ) {
				$found_primary = true;
				$primary_id    = $field->get_primary_id();
				$this->assertNotEmpty( $primary_id, 'Primary field should have a primary_id.' );
				break;
			}
		}

		$this->assertTrue( $found_primary, 'Should find at least one primary field in the default group.' );
	}
}
