<?php
/**
 * Handles connection to the WPUM database tables.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM DB base class
 *
 * @copyright   Copyright (c) 2015, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
abstract class WPUM_DB {

	/**
	 * The name of our database table
	 *
	 * @var string
	 */
	public $table_name;

	/**
	 * The version of our database table
	 *
	 * @var string
	 */
	public $version;

	/**
	 * The name of the primary column
	 *
	 * @var string
	 */
	public $primary_key;

	/**
	 * Get things started
	 */
	public function __construct() {}

	/**
	 * Whitelist of columns
	 *
	 * @access  public
	 * @return  array
	 */
	public function get_columns() {
		return array();
	}

	/**
	 * Default column values
	 *
	 * @access  public
	 * @return  array
	 */
	public function get_column_defaults() {
		return array();
	}

	/**
	 * Retrieve a row by the primary key
	 *
	 * @param int $row_id
	 *
	 * @return  object
	 */
	public function get( $row_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) ); // phpcs:ignore
	}

	/**
	 * Retrieve a row by a specific column / value
	 *
	 * @param string $column
	 * @param int    $row_id
	 *
	 * @return  object
	 */
	public function get_by( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $this->table_name WHERE $column = %s LIMIT 1;", $row_id ) ); // phpcs:ignore
	}

	/**
	 * Retrieve a specific column's value by the primary key
	 *
	 * @param string $column
	 * @param int    $row_id
	 *
	 * @return  string
	 */
	public function get_column( $column, $row_id ) {
		global $wpdb;
		$column = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $this->primary_key = %s LIMIT 1;", $row_id ) ); // phpcs:ignore
	}

	/**
	 * Retrieve a specific column's value by the the specified column / value
	 *
	 * @param string $column
	 * @param string $column_where
	 * @param string $column_value
	 *
	 * @return string
	 */
	public function get_column_by( $column, $column_where, $column_value ) {
		global $wpdb;
		$column_where = esc_sql( $column_where );
		$column       = esc_sql( $column );

		return $wpdb->get_var( $wpdb->prepare( "SELECT $column FROM $this->table_name WHERE $column_where = %s LIMIT 1;", $column_value ) ); // phpcs:ignore
	}

	/**
	 * Insert a new row
	 *
	 * @param array  $data
	 * @param string $type
	 *
	 * @return int
	 */
	public function insert( $data, $type = '' ) {
		global $wpdb;

		// Set default values
		$data = wp_parse_args( $data, $this->get_column_defaults() );

		do_action( 'wpum_pre_insert_' . $type, $data );

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		$wpdb->insert( $this->table_name, $data, $column_formats ); // phpcs:ignore
		$wpdb_insert_id = $wpdb->insert_id;

		do_action( 'wpum_post_insert_' . $type, $wpdb_insert_id, $data );

		return $wpdb_insert_id;
	}

	/**
	 * Update a row
	 *
	 * @param int    $row_id
	 * @param array  $data
	 * @param string $where
	 *
	 * @return  bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {
		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( empty( $where ) ) {
			$where = $this->primary_key;
		}

		// Initialise column format array
		$column_formats = $this->get_columns();

		// Force fields to lower case
		$data = array_change_key_case( $data );

		// White list columns
		$data = array_intersect_key( $data, $column_formats );

		// Reorder $column_formats to match the order of columns given in $data
		$data_keys      = array_keys( $data );
		$column_formats = array_merge( array_flip( $data_keys ), $column_formats );

		if ( ! $wpdb->update( $this->table_name, $data, array( $where => $row_id ), $column_formats ) ) { // phpcs:ignore
			return false;
		}

		return true;
	}

	/**
	 * Delete a row identified by the primary key
	 *
	 * @param int $row_id
	 *
	 * @return bool
	 */
	public function delete( $row_id = 0 ) {
		global $wpdb;

		// Row ID must be positive integer
		$row_id = absint( $row_id );

		if ( empty( $row_id ) ) {
			return false;
		}

		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM $this->table_name WHERE $this->primary_key = %d", $row_id ) ) ) { // phpcs:ignore
			return false;
		}

		return true;
	}

	/**
	 * Check if the given table exists
	 *
	 * @param  string $table The table name
	 * @return bool          If the table name exists
	 */
	public function table_exists( $table ) {
		global $wpdb;
		$table = sanitize_text_field( $table );

		return $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table; // phpcs:ignore
	}

	/**
	 * Check if the table was ever installed
	 *
	 * @return bool Returns if the customers table was installed and upgrade routine run
	 */
	public function installed() {
		return $this->table_exists( $this->table_name );
	}

}
