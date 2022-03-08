<?php

namespace WPUserManager\WPUMStripe\Models;


class Invoice
{

	public $id;
	public $user_id;
	public $invoice_id;
	public $total;
	public $currency;
	public $created_at;
	public $customer;

	/**
	 * Invoice constructor.
	 *
	 * @param $data
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
