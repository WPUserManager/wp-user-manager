<?php
/**
 * Handles connection with the db to manage registration forms.
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
 * WPUM_DB_Registration_Forms Class
 */
class WPUM_DB_Registration_Forms extends WPUM_DB {

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @var    string
	 */
	public $cache_group = 'registration_forms';

	/**
	 * Initialise object variables and register table.
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'wpum_registration_forms';
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
			'id'   => '%d',
			'name' => '%s',
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
			'id'   => 0,
			'name' => '',
		);
	}

	/**
	 * Insert a new registration form.
	 *
	 * @access public
	 *
	 * @param array  $data
	 * @param string $type
	 *
	 * @return int ID of the inserted form.
	 */
	public function insert( $data, $type = '' ) {
		$result = parent::insert( $data, $type );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Update a registration form.
	 *
	 * @access public
	 * @param int                $row_id form ID.
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
	 * Delete registration form.
	 *
	 * @access public
	 * @param int $row_id ID of the form to delete.
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
	 * Sets the last_changed cache key for groups.
	 *
	 * @access public
	 */
	public function set_last_changed() {
		wp_cache_set( 'last_changed', microtime(), $this->cache_group );
	}

	/**
	 * Retrieves the value of the last_changed cache key for groups.
	 *
	 * @access public
	 * @return string Value of the last_changed cache key for groups.
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
		return md5( 'wpum_registration_forms_' . serialize( $args ) ); // phpcs:ignore
	}

	/**
	 * @param array $args
	 *
	 * @return string
	 */
	public function get_cache_key_from_args( $args = array() ) {
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
			'number'  => 20,
			'offset'  => 0,
			'search'  => '',
			'orderby' => 'id',
			'order'   => 'ASC',
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
	 * Retrieve forms from the database
	 *
	 * @access public
	 *
	 * @param array $args {
	 *      Query arguments.
	 * }
	 *
	 * @return array $forms Array of `WPUM_Registration_Form` objects.
	 */
	public function get_forms( $args = array() ) {
		global $wpdb;

		$args = $this->get_args( $args );

		$where = $this->parse_where( $args );

		$cache_key = $this->get_cache_key( $args );

		$forms = wp_cache_get( $cache_key, $this->cache_group );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $forms ) {
			$forms = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $this->table_name $where ORDER BY {$args['orderby']} {$args['order']} LIMIT %d,%d;", absint( $args['offset'] ), absint( $args['number'] ) ), 0 ); // phpcs:ignore

			if ( ! empty( $forms ) ) {
				foreach ( $forms as $key => $form ) {
					$forms[ $key ] = new WPUM_Registration_Form( $form );
				}

				wp_cache_set( $cache_key, $forms, $this->cache_group, 3600 );
			}
		}

		return $forms;
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

		if ( ! empty( $where ) ) {
			$where = ' WHERE 1=1 ' . $where;
		}

		return $where;
	}

}
