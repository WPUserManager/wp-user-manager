<?php
/**
 * Handles storage of the custom fields groups.
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
 * Setup the global "wpum_fieldsgroups" database table
 */
final class WPUM_DB_Table_Fields_Groups extends WPUM_DB_Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = 'wpum_fieldsgroups';

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
			group_order bigint(20) unsigned NOT NULL default '0',
			is_primary bool NOT NULL DEFAULT '0',
			name varchar(190) NOT NULL default '',
			description longtext DEFAULT NULL,
			PRIMARY KEY (id),
			KEY name (name(190)),
			KEY group_order (group_order)";
	}

	/**
	 * Handle schema changes
	 *
	 * @access protected
	 * @return void
	 */
	protected function upgrade() {}

}
