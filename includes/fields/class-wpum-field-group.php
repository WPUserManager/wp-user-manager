<?php
/**
 * Database abstraction layer to work with field groups.
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
 * WPUM_Field_Group
 */
class WPUM_Field_Group {

	/**
	 * Group ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Group order number.
	 *
	 * @access protected
	 * @var int
	 */
	protected $group_order = 0;

	/**
	 * Wether the group is the primary group or not.
	 *
	 * @var boolean
	 */
	protected $is_primary = false;

	/**
	 * Group Name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = null;

	/**
	 * Group Description.
	 *
	 * @access protected
	 * @var string
	 */
	protected $description = null;

	/**
	 * Number of fields contained within this group.
	 *
	 * @var integer
	 */
	protected $count = 0;

	/**
	 * Holds fields for the current group.
	 *
	 * @var array
	 */
	protected $fields;

	/**
	 * The Database Abstraction
	 *
	 * @var WPUM_DB_Fields_Groups
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @param mixed $_id_or_group
	 */
	public function __construct( $_id_or_group = false ) {

		$this->db = new WPUM_DB_Fields_Groups();

		if ( empty( $_id_or_group ) ) {
			return false;
		}

		if ( is_a( $_id_or_group, 'WPUM_Field_Group' ) ) {
			$group = $_id_or_group;
		} else {
			$_id_or_group = intval( $_id_or_group );
			$group        = $this->db->get( $_id_or_group );
		}

		if ( $group ) {
			$this->setup_field_group( $group );
		} else {
			return false;
		}

	}

	/**
	 * Magic __get function to dispatch a call to retrieve a private property.
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} else {
			// translators: property name
			return new WP_Error( 'wpum-field-group-invalid-property', sprintf( __( 'Can\'t get property %s', 'wp-user-manager' ), $key ) );
		}
	}

	/**
	 * Set properties of the class.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function __set( $key, $value ) {
		$this->$key = $value;
	}

	/**
	 * Setup the field group.
	 *
	 * @param mixed $group
	 *
	 * @return bool
	 */
	private function setup_field_group( $group = null ) {

		if ( null === $group ) {
			return false;
		}

		if ( ! is_object( $group ) ) {
			return false;
		}

		if ( is_wp_error( $group ) ) {
			return false;
		}

		foreach ( $group as $key => $value ) {
			switch ( $key ) {
				default:
					$this->$key = $value;
					break;
			}
		}

		if ( ! empty( $this->id ) ) {
			$this->count = $this->count_fields( $this->id );
			return true;
		}

		return false;

	}

	/**
	 * Retrieve the group id.
	 *
	 * @return string
	 */
	public function get_ID() {
		return $this->id;
	}

	/**
	 * Retrieve the group name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get group order.
	 *
	 * @return string
	 */
	public function get_group_order() {
		return $this->group_order;
	}

	/**
	 * Retrieve the group description.
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Check if this is the primary group.
	 *
	 * @return boolean
	 */
	public function is_primary() {
		return $this->is_primary;
	}

	/**
	 * Retrieve the amount of fields stored for the fields group.
	 *
	 * @return int
	 */
	public function get_count() {
		return $this->count;
	}

	/**
	 * Retrive the fields stored for this group.
	 *
	 * @return array
	 */
	public function get_fields() {
		return $this->fields;
	}

	/**
	 * Check if a group exists.
	 *
	 * @return bool
	 */
	public function exists() {
		if ( ! $this->id > 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Add a new field group or update an existing one.
	 *
	 * @param array $args
	 *
	 * @return bool|int
	 */
	public function add( $args ) {

		if ( empty( $args['name'] ) ) {
			return false;
		}

		if ( ! empty( $this->id ) && $this->exists() ) {

			return $this->update( $args );

		} else {

			$args = apply_filters( 'wpum_insert_field_group', $args );
			$args = $this->sanitize_columns( $args );

			do_action( 'wpum_pre_insert_field_group', $args );

			foreach ( $args as $key => $value ) {
				$this->$key = $value;
			}

			$id = $this->db->insert( $args );
			if ( $id ) {
				$this->id = $id;
				$this->setup_field_group( $id );
			}
		}

		do_action( 'wpum_post_insert_field_group', $args, $this->id );

		return $id;

	}

	/**
	 * Update an existing field group.
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function update( $args ) {

		$ret  = false;
		$args = apply_filters( 'wpum_update_field_group', $args, $this->id );
		$args = $this->sanitize_columns( $args );

		do_action( 'wpum_pre_update_field_group', $args, $this->id );

		if ( count( array_intersect_key( $args, $this->db->get_columns() ) ) > 0 ) {
			if ( $this->db->update( $this->id, $args ) ) {
				$group = $this->db->get( $this->id );
				$this->setup_field_group( $group );
				$ret = true;
			}
		} elseif ( 0 === count( array_intersect_key( $args, $this->db->get_columns() ) ) ) {
			$group = $this->db->get( $this->id );
			$this->setup_field_group( $group );
			$ret = true;
		}

		do_action( 'wpum_post_update_field_group', $args, $this->id );

		return $ret;

	}

	/**
	 * Sanitize columns before adding a group to the database.
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	private function sanitize_columns( $data ) {

		$columns        = $this->db->get_columns();
		$default_values = $this->db->get_column_defaults();

		foreach ( $columns as $key => $type ) {

			// Only sanitize data that we were provided
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch ( $type ) {
				case '%s':
					if ( is_array( $data[ $key ] ) ) {
						$data[ $key ] = wp_json_encode( $data[ $key ] );
					} else {
						$data[ $key ] = sanitize_text_field( $data[ $key ] );
					}
					break;
				case '%d':
					if ( ! is_numeric( $data[ $key ] ) || absint( $data[ $key ] ) !== (int) $data[ $key ] ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = absint( $data[ $key ] );
					}
					break;
				default:
					$data[ $key ] = sanitize_text_field( $data[ $key ] );
					break;
			}
		}

		return $data;

	}

	/**
	 * Count fields within a group.
	 *
	 * @param int $group_id
	 *
	 * @return bool|int
	 */
	public function count_fields( $group_id = false ) {

		global $wpdb;

		if ( ! $group_id ) {
			return false;
		}

		$table_name = WPUM()->fields->table_name;

		$sql = "
			SELECT COUNT(*) FROM {$table_name}
			WHERE group_id = %d
		";

		$results = (array) $wpdb->get_results( $wpdb->prepare( $sql, $group_id ), ARRAY_A ); // phpcs:ignore
		$count   = array_values( $results[0] )[0];

		return $count;
	}

}
