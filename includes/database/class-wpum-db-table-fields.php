<?php
/**
 * Handles storage of the custom fields.
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
 * Setup the global "wpum_fields" database table
 */
final class WPUM_DB_Table_Fields extends WPUM_DB_Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = 'wpum_fields';

	/**
	 * Database version
	 *
	 * @access protected
	 * @var int
	 */
	protected $version = 201801170001;

		/**
		 * Setup the database schema
		 *
		 * @access protected
		 * @return void
		 */
	protected function set_schema() {
		$max_index_length = 191;
		$this->schema     = "id bigint(20) unsigned NOT NULL auto_increment,
			group_id bigint(20) unsigned NOT NULL default '0',
			field_order bigint(20) unsigned NOT NULL default '0',
			type varchar(20) NOT NULL default 'text',
			name varchar(255) NOT NULL default '',
			description longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY group_id (group_id),
			KEY field_order (field_order)";
	}

	/**
	 * Handle schema changes
	 *
	 * @access protected
	 * @return void
	 */
	protected function upgrade() {}

}
