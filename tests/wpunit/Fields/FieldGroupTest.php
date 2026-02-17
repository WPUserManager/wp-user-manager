<?php
/**
 * Tests for the WPUM field group system.
 */

require_once __DIR__ . '/FieldsTestCase.php';

class FieldGroupTest extends FieldsTestCase {

	/**
	 * Test creating a new field group.
	 */
	public function test_create_field_group() {
		$group_id = $this->groups_db->insert( array(
			'name'        => 'Test Group',
			'description' => 'A test field group.',
			'group_order' => 10,
		) );

		$this->assertGreaterThan( 0, $group_id, 'Group insert should return a positive ID.' );

		$group = new \WPUM_Field_Group( $group_id );
		$this->assertEquals( 'Test Group', $group->get_name() );
	}

	/**
	 * Test updating a field group.
	 */
	public function test_update_field_group() {
		$group_id = $this->groups_db->insert( array(
			'name'        => 'Original Name',
			'description' => 'Original description.',
		) );

		$updated = $this->groups_db->update( $group_id, array(
			'name'        => 'Updated Name',
			'description' => 'Updated description.',
		) );

		$this->assertTrue( $updated, 'Group update should return true.' );

		$group = new \WPUM_Field_Group( $group_id );
		$this->assertEquals( 'Updated Name', $group->get_name() );
	}

	/**
	 * Test deleting a field group.
	 */
	public function test_delete_field_group() {
		$group_id = $this->groups_db->insert( array(
			'name'        => 'Group To Delete',
			'description' => 'Will be deleted.',
		) );

		$deleted = $this->groups_db->delete( $group_id );
		$this->assertTrue( $deleted, 'Group delete should return true.' );

		$row = $this->groups_db->get( $group_id );
		$this->assertNull( $row, 'Deleted group should not be found.' );
	}

	/**
	 * Test that after default data install, the primary field group exists.
	 */
	public function test_default_field_group_exists() {
		$groups = $this->groups_db->get_groups( array(
			'primary' => true,
		) );

		$this->assertNotEmpty( $groups, 'At least one primary field group should exist.' );

		$primary_group = $groups[0];
		$this->assertInstanceOf( 'WPUM_Field_Group', $primary_group );
	}

	/**
	 * Test field group ordering is respected.
	 */
	public function test_field_group_ordering() {
		$group_a = $this->groups_db->insert( array(
			'name'        => 'Group A',
			'group_order' => 20,
		) );

		$group_b = $this->groups_db->insert( array(
			'name'        => 'Group B',
			'group_order' => 5,
		) );

		$groups = $this->groups_db->get_groups( array(
			'orderby' => 'group_order',
			'order'   => 'ASC',
			'number'  => 100,
		) );

		$group_ids = array_map( function( $g ) {
			return $g->get_ID();
		}, $groups );

		$pos_b = array_search( $group_b, $group_ids );
		$pos_a = array_search( $group_a, $group_ids );

		$this->assertLessThan( $pos_a, $pos_b, 'Group B (order 5) should come before Group A (order 20).' );
	}
}
