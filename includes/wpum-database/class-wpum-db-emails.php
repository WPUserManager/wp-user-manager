<?php
/**
 * Handles connection with the db to manage emails.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPUM_DB_Emails Class
 */
class WPUM_DB_Emails extends WPUM_DB {

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @var    string
	 */
	public $cache_group = 'emails';

	/**
	 * Initialise object variables and register table.
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'wpum_emails';
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
			'id'                    => '%d',
			'email_key'             => '%s',
			'email_name'            => '%s',
			'email_description'     => '%s',
            'email_recipient'       => '%s',
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
			'id'                    => 0,
			'email_key'             => '',
			'email_name'            => '',
			'email_description'     => '',
            'email_recipient'       => '',
		);
	}

	/**
	 * Insert a new email.
	 *
	 * @access public
	 *
	 * @param array  $data
	 * @param string $type
	 *
	 * @return int ID of the inserted email.
	 */
	public function insert( $data, $type = '' ) {
		$result = parent::insert( $data, $type );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Delete email.
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
	 * Sets the last_changed cache key for emails.
	 *
	 * @access public
	 */
	public function set_last_changed() {
		wp_cache_set( 'last_changed', microtime(), $this->cache_group );
	}

	/**
	 * Retrieves the value of the last_changed cache key for emails.
	 *
	 * @access public
	 * @return string Value of the last_changed cache key for emails.
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
	 * Retrieve emails from the database
	 *
	 * @access public
	 *
	 * @param array $args
	 *
	 * @return array Array of email objects.
	 */
	public function get_emails( $args = array() ) {

		global $wpdb;

		$defaults = array(
			'number'   => -1,
			'offset'   => 0,
			'search'   => '',
			'orderby'  => 'id',
			'order'    => 'DESC'
		);

		$args = wp_parse_args( $args, $defaults );

		if ( $args['number'] < 1 ) {
			$args['number'] = 999999999999;
		}

		if ( isset( $args['search'] ) && ! empty( $args['search'] ) ) {
			$args['search'] = $wpdb->esc_like( $args['search'] );
		}

		$where = $this->parse_where( $args );

		$args['orderby'] = ! array_key_exists( $args['orderby'], $this->get_columns() ) ? 'id' : $args['orderby'];

		$cache_key = md5( 'wpum_emails_' . serialize( $args ) );

		$emails = wp_cache_get( $cache_key, $this->cache_group );

		$args['orderby'] = esc_sql( $args['orderby'] );
		$args['order']   = esc_sql( $args['order'] );

		if ( false === $emails ) {
			$emails = $wpdb->get_results( $wpdb->prepare(
				"
					SELECT *
					FROM $this->table_name
					$where
					ORDER BY {$args['orderby']} {$args['order']}
					LIMIT %d,%d;
				", absint( $args['offset'] ), absint( $args['number'] ) ), ARRAY_A );

			if ( ! empty( $emails ) ) {
				wp_cache_set( $cache_key, $emails, $this->cache_group, 3600 );
			}
		}

		return $emails;

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

		if ( ! empty( $where ) ) {
			$where = ' WHERE 1=1 ' . $where;
		}

		return $where;
	}

}
