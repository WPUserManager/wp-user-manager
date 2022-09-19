<?php

namespace WPUserManager\WPUMStripe\Controllers;

class Subscriptions extends \WPUM_DB {

	/**
	 * The name of the cache group.
	 *
	 * @access public
	 * @var    string
	 */
	public $cache_group = 'stripe_subscriptions';

	/**
	 * Initialise object variables and register table.
	 */
	public function __construct() {
		global $wpdb;

		$this->table_name  = $wpdb->prefix . 'wpum_stripe_subscriptions';
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
			'id'              => '%d',
			'user_id'         => '%d',
			'customer_id'     => '%s',
			'plan_id'         => '%s',
			'subscription_id' => '%s',
			'trial_ends_at'   => '%s',
			'ends_at'         => '%s',
			'created_at'      => '%s',
			'updated_at'      => '%s',
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
			'id'              => 0,
			'user_id'         => 0,
			'customer_id'     => '',
			'plan_id'         => '',
			'subscription_id' => '',
			'trial_ends_at'   => '',
			'ends_at'         => '',
			'created_at'      => '',
			'updated_at'      => '',
		);
	}

	/**
	 * Insert subscription.
	 *
	 * @access public
	 *
	 * @param array $data
	 *
	 * @return int ID of the inserted coupon.
	 */
	public function insert( $data, $type = '' ) {
		$data['created_at'] = current_time( 'mysql' );
		$result             = parent::insert( $data, $type );

		if ( $result ) {
			$this->set_last_changed();
		}

		return $result;
	}

	/**
	 * Update subscription.
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
	 * Delete subscription.
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

	public function where( $key, $value ) {
		global $wpdb;
		$where = $this->parse_where( array( $key => $value ) );

		return $wpdb->get_row( "SELECT *
					FROM $this->table_name
					$where
					ORDER BY created_at DESC
					LIMIT 1;
				" );
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

		if ( ! empty( $args['customer_id'] ) ) {
			$customer_id = $args['customer_id'];
			$where      .= " AND customer_id = '$customer_id' ";
		}

		if ( ! empty( $args['user_id'] ) ) {
			$customer_id = $args['user_id'];
			$where      .= " AND user_id = '$customer_id' ";
		}

		if ( ! empty( $args['subscription_id'] ) ) {
			$subscription_id = $args['subscription_id'];
			$where          .= " AND subscription_id = '$subscription_id'";
		}

		if ( ! empty( $args['plan_id'] ) ) {
			$plan_id = $args['plan_id'];
			$where  .= " AND plan_id = '$plan_id' ";
		}

		if ( ! empty( $where ) ) {
			$where = ' WHERE 1=1 ' . $where;
		}

		return $where;
	}
}