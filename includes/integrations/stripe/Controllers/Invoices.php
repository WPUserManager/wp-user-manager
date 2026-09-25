<?php
/**
 * Handles the Stripe Invoices controller
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe\Controllers;

use WPUserManager\Stripe\Models\Invoice;

/**
 * Invoices
 */
class Invoices extends \WPUM_DB {

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @var    string
	 */
	public $cache_group = 'stripe_invoices';

	/**
	 * @var string
	 */
	protected $gateway_mode;

	/**
	 * Initialise object variables and register table.
	 *
	 * @param string $gateway_mode
	 */
	public function __construct( $gateway_mode ) {
		global $wpdb;

		$this->table_name   = $wpdb->prefix . 'wpum_stripe_invoices';
		$this->primary_key  = 'id';
		$this->version      = '1.0';
		$this->gateway_mode = $gateway_mode;

		parent::__construct();
	}

	/**
	 * Retrieve table columns and data types.
	 *
	 * @access public
	 * @return array Array of table columns and data types.
	 */
	public function get_columns() {
		return array(
			'id'           => '%d',
			'user_id'      => '%d',
			'invoice_id'   => '%s',
			'total'        => '%d',
			'currency'     => '%s',
			'gateway_mode' => '%s',
			'created_at'   => '%s',
			'updated_at'   => '%s',
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
			'id'           => 0,
			'user_id'      => 0,
			'invoice_id'   => '',
			'total'        => 0,
			'currency'     => '',
			'gateway_mode' => '',
			'created_at'   => '',
			'updated_at'   => '',
		);
	}

	/**
	 * Insert invoice
	 *
	 * @access public
	 *
	 * @param array  $data
	 * @param string $type
	 *
	 * @return int ID of the inserted coupon.
	 */
	public function insert( $data, $type = '' ) {
		$data['created_at']   = current_time( 'mysql' );
		$data['gateway_mode'] = $this->gateway_mode;
		$result               = parent::insert( $data, $type );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Update invoice
	 *
	 * @access public
	 *
	 * @param int                $row_id coupon ID.
	 * @param array              $data
	 * @param mixed string|array $where Where clause to filter update.
	 *
	 * @return  bool
	 */
	public function update( $row_id, $data = array(), $where = '' ) {
		$data['updated_at'] = current_time( 'mysql' );
		$result             = parent::update( $row_id, $data, $where );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Delete invoice
	 *
	 * @access public
	 *
	 * @param int $row_id ID of the coupon to delete.
	 *
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
	 * @param int $id
	 *
	 * @return false|Invoice
	 */
	public function find( $id ) {
		$invoice = $this->where( 'id', $id );

		if ( isset( $invoice[0] ) ) {
			return new Invoice( (array) $invoice[0] );
		}

		return false;
	}

	/**
	 * @param mixed $key
	 * @param mixed $value
	 *
	 * @return array|object|\stdClass[]|null
	 */
	public function where( $key, $value ) {
		global $wpdb;
		$where = $this->parse_where( array( $key => $value ) );

		return $wpdb->get_results( "SELECT * FROM $this->table_name $where ORDER BY created_at DESC" ); // phpcs:ignore
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

		if ( ! empty( $args['invoice_id'] ) ) {
			$invoice_id = $args['invoice_id'];
			$where     .= " AND invoice_id = '$invoice_id' ";
		}

		if ( ! empty( $args['user_id'] ) ) {
			$customer_id = $args['user_id'];
			$where      .= " AND user_id = '$customer_id' ";
		}

		if ( ! empty( $args['id'] ) ) {
			$id     = $args['id'];
			$where .= " AND id = '$id' ";
		}

		$where .= " AND gateway_mode = '$this->gateway_mode' ";

		return ' WHERE 1=1 ' . $where;
	}
}
