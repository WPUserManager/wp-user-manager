<?php

namespace WPUserManager\Stripe\Controllers;

use WPUM\Stripe\Stripe;
use WPUserManager\Stripe\Billing;
use WPUserManager\Stripe\Models\Product;

class Products {

	/**
	 * @var string
	 */
	protected $secret_key;

	/**
	 * @var string
	 */
	protected $gateway_mode;

	/**
	 * @var array|mixed
	 */
	protected $products;

	/**
	 * Products constructor.
	 *
	 * @param string $secret_key
	 * @param string $gateway_mode
	 */
	public function __construct( $secret_key, $gateway_mode ) {
		$this->secret_key   = $secret_key;
		$this->gateway_mode = $gateway_mode;
		$this->products     = $this->all();
	}

	protected function getProducts() {
		Stripe::setApiKey( $this->secret_key );

		$all_products = \WPUM\Stripe\Product::all();

		$products = array();
		foreach ( $all_products as $product ) {
			$all_prices = \WPUM\Stripe\Price::all( array( 'product' => $product->id ) );

			$save_product = $product->toArray();
			$prices       = array();
			foreach ( $all_prices->data as $price ) {
				$price_data                  = $price->toArray();
				$prices[ $price_data['id'] ] = $price_data;
			}

			$save_product['prices'] = $prices;
			$products[]             = $save_product;
		}

		return $products;
	}

	public function all( $force = false ) {
		$transient = get_transient( 'wpum_' . $this->gateway_mode . '_stripe_products' );

		if ( $transient && ! $force ) {
			$products = $transient;
		} else {
			$products = $this->getProducts();
			set_transient( 'wpum_' . $this->gateway_mode . '_stripe_products', $products, DAY_IN_SECONDS );
		}

		return $products;
	}

	/**
	 * @param $plan_id
	 *
	 * @return false|mixed
	 */
	public function get_by_plan( $plan_id ) {
		foreach ( $this->products as $product ) {
			if ( ! isset( $product['prices'] ) ) {
				continue;
			}

			if ( isset( $product['prices'][ $plan_id ] ) ) {
				return new Product( $plan_id, $product, $product['prices'][ $plan_id ] );
			}
		}

		return false;
	}

	public function get_plans() {
		$list     = array();
		$products = $this->all();
		foreach ( $products as $product ) {
			foreach ( $product['prices'] as $id => $price ) {
				$list[] = array(
					'label' => $product['name'] . ' - ' . html_entity_decode( \WPUserManager\Stripe\Stripe::currencySymbol( $price['currency'] ) ) . number_format( $price['unit_amount'] / 100 ),
					'value' => $id,
				);
			}
		}

		return $list;
	}

}
