<?php
/**
 * Handles storage of the registration forms.
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
final class WPUM_DB_Table_Registration_Forms extends WPUM_DB_Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = 'wpum_registration_forms';

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
		$this->schema = "id bigint(20) unsigned NOT NULL auto_increment,
			name varchar(255) NOT NULL default '',
			PRIMARY KEY (id)";
	}

	/**
	 * Handle schema changes
	 *
	 * @access protected
	 * @return void
	 */
	protected function upgrade() {}

}
