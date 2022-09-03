<?php
/**
 * Handles connection with the db to manage fields metas.
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
 * WPUM_DB_Field_Meta Class
 */
class WPUM_DB_Field_Meta extends WPUM_DB {

	/**
	 * Initialise object variables and register table.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name  = $wpdb->prefix . 'wpum_fieldmeta';
		$this->primary_key = 'meta_id';
		$this->version     = '1.0';
	}

	/**
	 * Retrieve table columns and data types.
	 *
	 * @access public
	 * @since 2.0.0
	 *
	 * @return array Array of table columns and data types.
	 */
	public function get_columns() {
		return array(
			'meta_id'    => '%d',
			'field_id'   => '%d',
			'meta_key'   => '%s',
			'meta_value' => '%s',
		);
	}

	/**
	 * Retrieve meta field for a field.
	 *
	 * @param integer $field_id
	 * @param string  $meta_key
	 * @param boolean $single If true, return only the first value of the specified meta_key.
	 *                        This parameter has no effect if meta_key is not specified.
	 *
	 * @return mixed
	 */
	public function get_meta( $field_id = 0, $meta_key = '', $single = false ) {
		$field_id = $this->sanitize_field_id( $field_id );
		if ( false === $field_id ) {
			return false;
		}
		return get_metadata( 'wpum_field', $field_id, $meta_key, $single );
	}

	/**
	 * @param int    $field_id
	 * @param string $meta_key
	 *
	 * @return bool
	 */
	public function meta_exists( $field_id = 0, $meta_key = '' ) {
		$field_id = $this->sanitize_field_id( $field_id );

		return metadata_exists( 'wpum_field', $field_id, $meta_key );
	}

	/**
	 * Add meta data field to a field.
	 *
	 * @param integer $field_id
	 * @param string  $meta_key
	 * @param mixed   $meta_value
	 * @param boolean $unique
	 * @return int|false
	 */
	public function add_meta( $field_id, $meta_key, $meta_value, $unique = false ) {
		$field_id = $this->sanitize_field_id( $field_id );
		if ( false === $field_id ) {
			return false;
		}
		return add_metadata( 'wpum_field', $field_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update meta data for a field.
	 *
	 * @param integer $field_id
	 * @param string  $meta_key
	 * @param mixed   $meta_value
	 * @param string  $prev_value
	 *
	 * @return bool|int
	 */
	public function update_meta( $field_id, $meta_key, $meta_value, $prev_value = '' ) {
		$field_id = $this->sanitize_field_id( $field_id );
		if ( false === $field_id ) {
			return false;
		}
		return update_metadata( 'wpum_field', $field_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete meta data for a field.
	 *
	 * @param integer $field_id
	 * @param string  $meta_key
	 * @param string  $meta_value
	 *
	 * @return bool
	 */
	public function delete_meta( $field_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'wpum_field', $field_id, $meta_key, $meta_value );
	}

	/**
	 * Given a field ID, make sure it's a positive number, greater than zero before inserting or adding.
	 *
	 * @param mixed $field_id
	 *
	 * @return false|int
	 */
	private function sanitize_field_id( $field_id ) {
		if ( ! is_numeric( $field_id ) ) {
			return false;
		}
		$field_id = (int) $field_id;
		// We were given a non positive number
		if ( absint( $field_id ) !== $field_id ) {
			return false;
		}
		if ( empty( $field_id ) ) {
			return false;
		}
		return absint( $field_id );
	}

}
