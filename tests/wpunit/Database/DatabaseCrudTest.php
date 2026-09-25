<?php
/**
 * Tests for the WPUM database abstraction layer.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class DatabaseCrudTest extends WPUMTestCase {

	/**
	 * @var \WPUM_DB_Fields_Groups
	 */
	protected $db;

	public function _setUp() {
		parent::_setUp();
		$this->db = new \WPUM_DB_Fields_Groups();
	}

	/**
	 * Test that insert fires pre and post hooks.
	 */
	public function test_insert_fires_hooks() {
		$pre_fired  = false;
		$post_fired = false;

		add_action( 'wpum_pre_insert_field_group', function() use ( &$pre_fired ) {
			$pre_fired = true;
		} );

		add_action( 'wpum_post_insert_field_group', function() use ( &$post_fired ) {
			$post_fired = true;
		} );

		$this->db->insert( array(
			'name'        => 'Hook Test Group',
			'description' => 'Testing hooks.',
		), 'field_group' );

		$this->assertTrue( $pre_fired, 'wpum_pre_insert_field_group action should fire.' );
		$this->assertTrue( $post_fired, 'wpum_post_insert_field_group action should fire.' );
	}

	/**
	 * Test the get_by() method retrieves a row by column.
	 */
	public function test_get_by_column() {
		$group_id = $this->db->insert( array(
			'name'        => 'Unique_GetBy_Name_' . wp_rand(),
			'description' => 'Get by test.',
		) );

		$row = $this->db->get_by( 'id', $group_id );

		$this->assertNotNull( $row, 'get_by should return a row.' );
		$this->assertEquals( $group_id, $row->id );
	}

	/**
	 * Test that columns are whitelisted during insert.
	 * Invalid columns should be silently ignored.
	 */
	public function test_column_whitelisting() {
		$group_id = $this->db->insert( array(
			'name'            => 'Whitelist Test',
			'invalid_column'  => 'should_be_ignored',
		) );

		$this->assertGreaterThan( 0, $group_id, 'Insert should succeed even with invalid columns.' );

		$row = $this->db->get( $group_id );
		$this->assertEquals( 'Whitelist Test', $row->name );
	}

	/**
	 * Test that get() returns null for non-existent IDs.
	 */
	public function test_get_nonexistent_returns_null() {
		$row = $this->db->get( 999999 );
		$this->assertNull( $row, 'get() should return null for non-existent row.' );
	}

	/**
	 * Test that delete with zero ID returns false.
	 */
	public function test_delete_zero_id_returns_false() {
		$result = $this->db->delete( 0 );
		$this->assertFalse( $result, 'delete(0) should return false.' );
	}

	/**
	 * Test that update with zero ID returns false.
	 */
	public function test_update_zero_id_returns_false() {
		$result = $this->db->update( 0, array( 'name' => 'test' ) );
		$this->assertFalse( $result, 'update(0) should return false.' );
	}

	/**
	 * Test the table_exists method.
	 */
	public function test_table_exists() {
		$this->assertTrue( $this->db->installed(), 'The fields_groups table should exist.' );
	}
}
