<?php
/**
 * Handles all the database tables of WPUM.
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
 * Setup the WPUM specific database table class, which sets the plugin
 * file variable for all future subclasses.
 */
class WPUM_DB_Table extends WPUM_WP_DB_Table {

	/**
	 * File passed to register_activation_hook()
	 *
	 * This is the same for all of EDD
	 *
	 * @access protected
	 * @var string
	 */
	protected $file = WPUM_PLUGIN_FILE;

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @return void
	 */
	protected function set_schema() {}

	/**
	 * Handle schema changes
	 *
	 * @access protected
	 * @return void
	 */
	protected function upgrade() {}

}
