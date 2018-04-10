<?php
/**
 * Database abstraction layer to work with the registration forms stored into the database.
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
class WPUM_Registration_Form {

	/**
	 * Form ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Form name.
	 *
	 * @access protected
	 * @var int
	 */
	protected $name = null;

	/**
	 * All the IDs of the fields that belong to this form.
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * The Database Abstraction
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @param mixed|boolean $_id
	 */
	public function __construct( $_id_or_form = false ) {

		$this->db = new WPUM_DB_Registration_Forms();

		if( empty( $_id_or_form ) ) {
			return false;
		}

		if( is_a( $_id_or_form, 'WPUM_DB_Registration_Forms' ) ) {
			$form = $_id_or_form;
		} else {
			$_id_or_form = intval( $_id_or_form );
			$form        = $this->db->get( $_id_or_form );
		}

		if ( $form ) {
			$this->setup_form( $form );
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
			return new WP_Error( 'wpum-registration-form-invalid-property', sprintf( __( 'Can\'t get property %s' ), $key ) );
		}
	}

	/**
	 * Setup registration form object.
	 *
	 * @param mixed $form
	 * @return void
	 */
	private function setup_form( $form = null ) {

		if ( null == $form ) {
			return false;
		}

		if ( ! is_object( $form ) ) {
			return false;
		}

		if ( is_wp_error( $form ) ) {
			return false;
		}

		foreach ( $form as $key => $value ) {
			switch ( $key ) {
				default:
					$this->$key = $value;
					break;
			}
		}

		if ( ! empty( $this->id ) ) {
			// $this->count = $this->count_fields( $this->id );
			return true;
		}

		return false;

	}

	/**
	 * Retrieve the form id.
	 *
	 * @return void
	 */
	public function get_ID() {
		return $this->id;
	}

	/**
	 * Retrieve the name of the form.
	 *
	 * @return void
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Retrieve the fields assigned to this form.
	 *
	 * @return string
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Check if a form exists.
	 *
	 * @return void
	 */
	public function exists() {
		if ( ! $this->id > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Update an existing registration form.
	 *
	 * @param array $args
	 * @return void
	 */
	public function update( $args ) {

		$ret  = false;
		$args = apply_filters( 'wpum_update_registration_form', $args, $this->id );
		$args = $this->sanitize_columns( $args );

		do_action( 'wpum_pre_update_registration_form', $args, $this->id );

		if ( count( array_intersect_key( $args, $this->db->get_columns() ) ) > 0 ) {
			if ( $this->db->update( $this->id, $args ) ) {
				$field = $this->db->get( $this->id );
				$this->setup_field( $field );
				$ret = true;
			}
		} elseif ( 0 === count( array_intersect_key( $args, $this->db->get_columns() ) ) ) {
			$field = $this->db->get( $this->id );
			$this->setup_field( $field );
			$ret = true;
		}

		do_action( 'wpum_post_update_registration_form', $args, $this->id );

		return $ret;

	}

	/**
	 * Sanitize columns before adding a group to the database.
	 *
	 * @param array $data
	 * @return void
	 */
	private function sanitize_columns( $data ) {

		$columns        = $this->db->get_columns();
		$default_values = $this->db->get_column_defaults();

		foreach ( $columns as $key => $type ) {

			// Only sanitize data that we were provided
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch( $type ) {
				case '%s':
					if( is_array( $data[$key] ) ) {
						$data[$key] = json_encode( $data[$key] );
					} else {
						$data[$key] = sanitize_text_field( $data[$key] );
					}
				break;
				case '%d':
					if ( ! is_numeric( $data[$key] ) || (int) $data[$key] !== absint( $data[$key] ) ) {
						$data[$key] = $default_values[$key];
					} else {
						$data[$key] = absint( $data[$key] );
					}
				break;
				default:
					$data[$key] = sanitize_text_field( $data[$key] );
				break;
			}

		}

		return $data;

	}

}
