<?php
/**
 * Handles the Stripe Invoice models
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe\Models;

/**
 * Invoice
 */
class Invoice {

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var int
	 */
	public $user_id;

	/**
	 * @var string
	 */
	public $invoice_id;

	/**
	 * @var int
	 */
	public $total;

	/**
	 * @var string
	 */
	public $currency;

	/**
	 * @var string
	 */
	public $created_at;

	/**
	 * @var User
	 */
	public $customer;

	/**
	 * Invoice constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		$this->customer = new User( $this->user_id );
	}

}
