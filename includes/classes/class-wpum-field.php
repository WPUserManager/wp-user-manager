<?php
/**
 * Database abstraction layer to work with fields stored into the database.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that stores the DB field object.
 */
class WPUM_Field {

	/**
	 * Field ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Group ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $group_id = 0;

	/**
	 * Field order.
	 *
	 * @access protected
	 * @var int
	 */
	protected $field_order = 0;

	/**
	 * Wether the field is a primary field or not.
	 *
	 * @var boolean
	 */
	protected $is_primary = false;

	/**
	 * Field Name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = null;

	/**
	 * Field Description.
	 *
	 * @access protected
	 * @var string
	 */
	protected $description = null;

	/**
	 * The Database Abstraction
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @param mixed|boolean $_id
	 */
	public function __construct( $_id_or_field = false ) {

		$this->db = new WPUM_DB_Fields();

		if( empty( $_id_or_field ) ) {
			return false;
		}

		if( is_a( $_id_or_field, 'WPUM_Field' ) ) {
			$field = $_id_or_field;
		} else {
			$_id_or_field = intval( $_id_or_field );
			$field        = $this->db->get( $_id_or_field );
		}

		if ( $field ) {
			$this->setup_field( $field );
		} else {
			return false;
		}

	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property.
	 *
	 * @param string $key
	 * @return void
	 */
	public function __get( $key ) {
		if( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} else {
			return new WP_Error( 'wpum-field-invalid-property', sprintf( __( 'Can\'t get property %s' ), $key ) );
		}
	}

	/**
	 * Setup the field.
	 *
	 * @param mixed $field
	 * @return void
	 */
	private function setup_field( $field = null ) {

		if ( null == $field ) {
			return false;
		}

		if ( ! is_object( $field ) ) {
			return false;
		}

		if ( is_wp_error( $field ) ) {
			return false;
		}

		foreach ( $field as $key => $value ) {
			switch ( $key ) {
				default:
					$this->$key = $value;
					break;
			}
		}

		if ( ! empty( $this->id ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Retrieve the field id.
	 *
	 * @return void
	 */
	public function get_ID() {
		return $this->id;
	}

	/**
	 * Retrieve the field name.
	 *
	 * @return void
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Retrieve the field description.
	 *
	 * @return void
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Check if this is a primary field.
	 *
	 * @return boolean
	 */
	public function is_primary() {
		return $this->is_primary;
	}

	/**
	 * Check if a field exists.
	 *
	 * @return void
	 */
	public function exists() {
		if ( ! $this->id > 0 ) {
			return false;
		}

		return true;
	}

}
