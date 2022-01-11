<?php
/**
 * Handles storage of the custom emails.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup the global "wpum_emails" database table
 */
final class WPUM_DB_Table_Emails extends WPUM_DB_Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = 'wpum_emails';

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
		$this->schema     = "`id` int(11) NOT NULL AUTO_INCREMENT,
        `email_key` varchar(255) NOT NULL,
        `email_name` varchar(255) NULL,
        `email_description` varchar(255) NULL,
        PRIMARY KEY (`id`)";
	}

	/**
	 * Handle schema changes
	 *
	 * @access protected
	 * @return void
	 */
	protected function upgrade() {}

}
