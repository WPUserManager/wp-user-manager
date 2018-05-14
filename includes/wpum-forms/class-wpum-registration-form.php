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
	 * Wether or not this form is the default form.
	 *
	 * @var boolean
	 */
	protected $is_default = false;

	/**
	 * Retrieve the user role assigned to this form.
	 *
	 * @var string
	 */
	protected $role = null;

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
			return new WP_Error( 'wpum-registration-form-invalid-property', sprintf( __( 'Can\'t get property %s', 'wp-user-manager' ), $key ) );
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
			$this->is_default = $this->get_meta( 'default' );
			$this->role       = $this->get_assigned_role();
			$this->fields     = $this->get_meta( 'fields' );
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
	 * Retrieve the amount of fields within this registration form.
	 *
	 * @return int
	 */
	public function get_fields_count() {
		return count( $this->fields );
	}

	/**
	 * Check wether or not this is the default registration form.
	 *
	 * @return boolean
	 */
	public function is_default() {
		return (bool) $this->is_default;
	}

	/**
	 * Retrieve the assigned role for this form.
	 *
	 * @return void
	 */
	public function get_role() {
		return $this->role;
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
	 * Retrieve the human friendly name.
	 *
	 * @return void
	 */
	private function get_assigned_role() {

		$role            = $this->get_meta( 'role' );
		$available_roles = wpum_get_roles( true );

		$criteria   = array( 'value' => $role );
		$found_role = wp_list_filter( $available_roles, $criteria );

		reset( $found_role );
		$first_key = key( $found_role );

		if( array_key_exists( $first_key, $available_roles ) ) {
			$role = $available_roles[ $first_key ]['label'];
		}

		return $role;

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

	/**
	 * Retrieve metadata for this registration form.
	 *
	 * @param   string $meta_key      The meta key to retrieve.
	 * @param   bool   $single        Whether to return a single value.
	 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single is true.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function get_meta( $meta_key = '', $single = true ) {
		return WPUM()->registration_form_meta->get_meta( $this->id, $meta_key, $single );
	}

	/**
	 * Add meta data for this registration form.
	 *
	 * @param   string $meta_key      Metadata name.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   bool   $unique        Optional, default is false. Whether the same key should not be added.
	 * @return  bool                  False for failure. True for success.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function add_meta( $meta_key = '', $meta_value, $unique = false ) {
		return WPUM()->registration_form_meta->add_meta( $this->id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update field meta for this registration form.
	 *
	 * @param   string $meta_key      Metadata key.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   mixed  $prev_value    Optional. Previous value to check before removing.
	 * @return  bool                  False on failure, true if success.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function update_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return WPUM()->registration_form_meta->update_meta( $this->id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Delete field meta for this registration form.
	 *
	 * @param   string $meta_key      Metadata key.
	 * @param   mixed  $meta_value    Metadata value.
	 * @param   mixed  $prev_value    Optional. Previous value to check before removing.
	 * @return  bool                  False on failure, true if success.
	 *
	 * @access  public
	 * @since   2.0
	 */
	public function delete_meta( $meta_key = '', $meta_value, $prev_value = '' ) {
		return WPUM()->registration_form_meta->delete_meta( $this->id, $meta_key, $meta_value, $prev_value );
	}

}
