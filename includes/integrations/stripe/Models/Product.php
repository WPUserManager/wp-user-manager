<?php
/**
 * Handles the Product
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe\Models;

/**
 * Product
 */
class Product {

	/**
	 * @var string
	 */
	public $id;

	/**
	 * @var mixed
	 */
	public $name;

	/**
	 * @var mixed
	 */
	public $type;

	/**
	 * @var mixed
	 */
	public $amount;

	/**
	 * @var false
	 */
	public $paid;

	/**
	 * @var int
	 */
	public $when_signed;

	/**
	 * @var mixed
	 */
	public $when_paid;

	/**
	 * Plan constructor.
	 *
	 * @param string $id
	 * @param array  $data
	 * @param array  $price_data
	 */
	public function __construct( $id = null, $data = array(), $price_data = array() ) {
		if ( $id ) {
			$this->id          = $id;
			$this->name        = $data['name'];
			$this->type        = $price_data['type'];
			$this->amount      = $price_data['unit_amount'];
			$this->paid        = false;
			$this->when_signed = time();
		}
	}

	/**
	 * @return bool
	 */
	public function is_recurring() {
		return 'one_time' !== $this->type;
	}

	/**
	 * @param array $data
	 */
	public function hydrate( $data ) {
		foreach ( $data as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	/**
	 * @return array
	 */
	public function to_array() {
		return get_object_vars( $this );
	}

	/**
	 * Set product as paid
	 */
	public function setPaid() {
		$this->paid      = true;
		$this->when_paid = time();
	}
}

