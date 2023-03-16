<?php
/**
 * Handles connection with the db to manage fields.
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
 * WPUM_DB_Fields Class
 */
class WPUM_DB_Fields extends WPUM_DB {

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @var    string
	 */
	public $cache_group = 'fields';

	/**
	 * Initialise object variables and register table.
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'wpum_fields';
		$this->primary_key = 'id';
		$this->version     = '1.0';
	}

	/**
	 * Retrieve table columns and data types.
	 *
	 * @access public
	 * @return array Array of table columns and data types.
	 */
	public function get_columns() {
		return array(
			'id'          => '%d',
			'group_id'    => '%d',
			'field_order' => '%d',
			'type'        => '%s',
			'name'        => '%s',
			'description' => '%s',
		);
	}

	/**
	 * Get default column values.
	 *
	 * @access public
	 * @return array Array of the default values for each column in the table.
	 */
	public function get_column_defaults() {
		return array(
			'id'          => 0,
			'group_id'    => 0,
			'field_order' => 0,
			'type'        => '',
			'name'        => '',
			'description' => '',
		);
	}

	/**
	 * Insert a new field.
	 *
	 * @access public
	 *
	 * @param array  $data
	 * @param string $type
	 *
	 * @return int ID of the inserted field.
	 */
	public function insert( $data, $type = '' ) {
		$result = parent::insert( $data, $type );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Update a field.
	 *
	 * @access public
	 * @param int                $row_id field ID.
	 * @param array              $data
	 * @param mixed string|array $where Where clause to filter update.
	 *
	 * @return  bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {
		$result = parent::update( $row_id, $data, $where );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Delete field.
	 *
	 * @access public
	 * @param int $row_id ID of the field to delete.
	 * @return bool True if deletion was successful, false otherwise.
	 */
	public function delete( $row_id = 0 ) {
		if ( empty( $row_id ) ) {
			return false;
		}

		$result = parent::delete( $row_id );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Sets the last_changed cache key for fields.
	 *
	 * @access public
	 */
	public function set_last_changed() {
		wp_cache_set( 'last_changed', microtime(), $this->cache_group );
	}

	/**
	 * Retrieves the value of the last_changed cache key for fields.
	 *
	 * @access public
	 * @return string Value of the last_changed cache key for fields.
	 */
	public function get_last_changed() {
		if ( function_exists( 'wp_cache_get_last_changed' ) ) {
			return wp_cache_get_last_changed( $this->cache_group );
		}

		$last_changed = wp_cache_get( 'last_changed', $this->cache_group );
		if ( ! $last_changed ) {
			$last_changed = microtime();
			wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
		}

		return $last_changed;
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	protected function get_cache_key( $args ) {
		return md5( 'wpum_fields_' . serialize( $args ) ); // phpcs:ignore
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_cache_key_from_args( $args ) {
		$args = $this->get_args( $args );

		return $this->get_cache_key( $args );
	}

	/**
	 * @param array $args
	 *
	 * @return array|object
	 */
	protected function get_args( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'number'   => -1,
			'offset'   => 0,
			'search'   => '',
			'group_id' => false,
			'orderby'  => 'id',
			'order'    => 'DESC',
			'user_id'  => false,
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		if ( isset( $args['search'] ) && ! empty( $args['search'] ) ) {
			$args['search'] = $wpdb->esc_like( $args['search'] );
		}

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'id' : $args['orderby'];

		return $args;
	}

	/**
	 * Retrieve fields from the database
	 *
	 * @access public
	 *
	 * @param array $args
	 *
	 * @return array $groups Array of `WPUM_Field` objects.
	 */
	public function get_fields( $args = array() ) {

		global $wpdb;

		$args = $this->get_args( $args );

		$where = $this->parse_where( $args );

		$cache_key = $this->get_cache_key( $args );

		$fields = wp_cache_get( $cache_key, $this->cache_group );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		$join_query = '';

		if ( ! empty( $args['parent'] ) ) {
			$meta_table = $wpdb->prefix . 'wpum_fieldmeta';
			$join_query = $wpdb->prepare( "JOIN $meta_table meta ON meta.wpum_field_id = id AND meta.meta_key = %s", 'parent_id' ); // phpcs:ignore
			$join_query = apply_filters( 'wpum_fields_join_query', $join_query, $this );

			$where = str_replace( '`group_id`', sprintf( 'meta.meta_value = %d AND `group_id`', intval( $args['parent'] ) ), $where );
		}

		if ( false === $fields ) {
			$fields = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $this->table_name $join_query $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ), 0 ); // phpcs:ignore

			if ( ! empty( $fields ) ) {
				$new_fields = array();
				foreach ( $fields as $key => $field ) {

					$field = new WPUM_Field( $field );
					$field->set_user_meta( $args['user_id'] );

					if ( empty( $args['parent'] ) && $field->get_parent_ID() > 0 ) {
						unset( $fields[ $key ] );
						continue;
					}
					$new_fields[] = $field;
				}
				$fields = $new_fields;

				wp_cache_set( $cache_key, $fields, $this->cache_group, 3600 );
			}
		}

		return $fields;

	}

	/**
	 * Parse the `WHERE` clause for the SQL query.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	private function parse_where( $args ) {
		$where = '';

		// Specific fields group.
		if ( ! empty( $args['group_id'] ) ) {
			if ( is_array( $args['group_id'] ) ) {
				$group_ids = implode( "','", array_map( 'sanitize_text_field', $args['group_id'] ) );
			} else {
				$group_ids = sanitize_text_field( $args['group_id'] );
			}
			$where .= " AND `group_id` IN( '{$group_ids}' ) ";
		}

		// Specific field types.
		if ( ! empty( $args['type'] ) ) {
			if ( is_array( $args['type'] ) ) {
				$types = implode( "','", array_map( 'sanitize_text_field', $args['type'] ) );
			} else {
				$types = sanitize_text_field( $args['type'] );
			}
			$where .= " AND `type` IN( '{$types}' ) ";
		}

		if ( ! empty( $where ) ) {
			$where = ' WHERE 1=1 ' . $where;
		}

		return $where;
	}

}
