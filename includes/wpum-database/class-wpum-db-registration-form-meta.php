<?php
/**
 * Handles connection with the db to manage registration forms metas.
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
 * WPUM_DB_Registration_Form_Meta Class
 */
class WPUM_DB_Registration_Form_Meta extends WPUM_DB {

	/**
	 * Initialise object variables and register table.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name  = $wpdb->prefix . 'wpum_registration_formmeta';
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
			'meta_id'              => '%d',
			'registration_form_id' => '%d',
			'meta_key'             => '%s',
			'meta_value'           => '%s',
		);
	}

	/**
	 * Retrieve meta field for a registration form.
	 *
	 * @param integer $form_id
	 * @param string  $meta_key
	 * @param boolean $single If true, return only the first value of the specified meta_key.
	 *                        This parameter has no effect if meta_key is not specified.
	 * @return mixed
	 */
	public function get_meta( $form_id = 0, $meta_key = '', $single = false ) {
		$form_id = $this->sanitize_form_id( $form_id );
		if ( false === $form_id ) {
			return false;
		}

		return get_metadata( 'wpum_registration_form', $form_id, $meta_key, $single );
	}

	/**
	 * Add meta data field to a registration form.
	 *
	 * @param integer $form_id
	 * @param string  $meta_key
	 * @param mixed   $meta_value
	 * @param boolean $unique
	 *
	 * @return false|int
	 */
	public function add_meta( $form_id, $meta_key, $meta_value, $unique = false ) {
		$form_id = $this->sanitize_form_id( $form_id );
		if ( false === $form_id ) {
			return false;
		}

		return add_metadata( 'wpum_registration_form', $form_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update meta data for a registration form.
	 *
	 * @param integer $form_id
	 * @param string  $meta_key
	 * @param mixed   $meta_value
	 * @param string  $prev_value
	 *
	 * @return int|bool
	 */
	public function update_meta( $form_id, $meta_key, $meta_value, $prev_value = '' ) {
		$form_id = $this->sanitize_form_id( $form_id );
		if ( false === $form_id ) {
			return false;
		}

		return update_metadata( 'wpum_registration_form', $form_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete meta data for a registration form.
	 *
	 * @param integer $form_id
	 * @param string  $meta_key
	 * @param string  $meta_value
	 *
	 * @return bool
	 */
	public function delete_meta( $form_id = 0, $meta_key = '', $meta_value = '' ) {
		return delete_metadata( 'wpum_registration_form', $form_id, $meta_key, $meta_value );
	}

	/**
	 * Given a field ID, make sure it's a positive number, greater than zero before inserting or adding.
	 *
	 * @param mixed $form_id
	 *
	 * @return false|int
	 */
	private function sanitize_form_id( $form_id ) {
		if ( ! is_numeric( $form_id ) ) {
			return false;
		}
		$form_id = (int) $form_id;
		// We were given a non positive number
		if ( absint( $form_id ) !== $form_id ) {
			return false;
		}
		if ( empty( $form_id ) ) {
			return false;
		}

		return absint( $form_id );
	}

}
