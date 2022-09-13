<?php


namespace WPUserManager\WPUMStripe\Models;

class Product {

	public $id;
	public $name;
	public $type;
	public $amount;
	public $paid;
	public $when_signed;
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

	public function is_recurring() {
		return 'one_time' !== $this->type;
	}

	/**
	 * @param $data
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

	public function setPaid() {
		$this->paid      = true;
		$this->when_paid = time();
	}
}

