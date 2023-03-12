<?php
/**
 * Handles storage of the stripe invoices.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM_DB_Table_Stripe_Invoices
 */
final class WPUM_DB_Table_Stripe_Invoices extends WPUM_DB_Table {

	/**
	 * Table name
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = 'wpum_stripe_invoices';

	/**
	 * Database version
	 *
	 * @access protected
	 * @var int
	 */
	protected $version = 202106090001;

	/**
	 * Setup the database schema
	 *
	 * @access protected
	 * @return void
	 */
	protected function set_schema() {

		$this->schema = '`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `invoice_id` varchar(255) NOT NULL,
  `total` decimal(8,2) NOT NULL,
  `currency` varchar(20) NOT NULL,
  `gateway_mode` varchar(4) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
			PRIMARY KEY (id)';
	}

	/**
	 * Handle schema changes
	 *
	 * @access protected
	 * @return void
	 */
	protected function upgrade() {
	}
}
