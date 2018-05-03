<?php
/**
 * Handles connection with the db to manage the search fields.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPUM_DB_Search_Fields Class
 */
class WPUM_DB_Search_Fields extends WPUM_DB {

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @var    string
	 */
	public $cache_group = 'search_fields';

	/**
	 * Initialise object variables and register table.
	 */
	public function __construct() {
		global $wpdb;
		$this->table_name  = $wpdb->prefix . 'wpum_search_fields';
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
			'meta_key'    => '%d',
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
			'id'        => 0,
			'meta_kery' => '',
		);
	}

}
