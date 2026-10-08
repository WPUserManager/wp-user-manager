<?php
/**
 * Handles the Stripe Products controller
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe\Controllers;

use WPUM\Stripe\Stripe;
use WPUserManager\Stripe\Billing;
use WPUserManager\Stripe\Models\Product;

/**
 * Products
 */
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

	/**
	 * Fetch the active products, with their active prices, from Stripe.
	 *
	 * @return array|false False if Stripe returned an error.
	 */
	protected function getProducts() {
		Stripe::setApiKey( $this->secret_key );

		$products = array();

		try {
			$all_products = \WPUM\Stripe\Product::all(
				array(
					'active' => true,
					'limit'  => 100,
				)
			);

			foreach ( $all_products as $product ) {
				$all_prices = \WPUM\Stripe\Price::all(
					array(
						'product' => $product->id,
						'active'  => true,
					)
				);

				$save_product = $product->toArray();
				$prices       = array();
				foreach ( $all_prices->data as $price ) {
					$price_data                  = $price->toArray();
					$prices[ $price_data['id'] ] = $price_data;
				}

				$save_product['prices'] = $prices;
				$products[]             = $save_product;
			}
		} catch ( \WPUM\Stripe\Exception\ApiErrorException $exception ) {
			return false;
		}

		return $products;
	}

	/**
	 * Get the products, from the cache if possible.
	 *
	 * If Stripe can't be reached, fall back to the last list fetched successfully
	 * and try again in a few minutes, rather than on every request.
	 *
	 * @param false $force
	 *
	 * @return array|mixed
	 */
	public function all( $force = false ) {
		$cache_key = 'wpum_' . $this->gateway_mode . '_stripe_products';
		$transient = get_transient( $cache_key );

		if ( false !== $transient && ! $force ) {
			return $transient;
		}

		$products = $this->getProducts();

		if ( false === $products ) {
			$products = get_option( $cache_key . '_last_good', array() );
			set_transient( $cache_key, $products, 5 * MINUTE_IN_SECONDS );

			return $products;
		}

		set_transient( $cache_key, $products, DAY_IN_SECONDS );
		update_option( $cache_key . '_last_good', $products, false );

		return $products;
	}

	/**
	 * Forget the cached products, including the last good list. Use when the Stripe account changes.
	 *
	 * @param string $gateway_mode
	 */
	public static function forget( $gateway_mode ) {
		delete_transient( 'wpum_' . $gateway_mode . '_stripe_products' );
		delete_option( 'wpum_' . $gateway_mode . '_stripe_products_last_good' );
	}

	/**
	 * @param string     $plan_id
	 * @param array|null $allowed_plan_ids Only resolve these price IDs. Null allows any.
	 *
	 * @return false|mixed
	 */
	public function get_by_plan( $plan_id, $allowed_plan_ids = null ) {
		if ( is_array( $allowed_plan_ids ) && ! in_array( $plan_id, $allowed_plan_ids, true ) ) {
			return false;
		}

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

	/**
	 * @param array $allowed
	 *
	 * @return array
	 */
	public function get_plans( $allowed = array() ) {
		$list     = array();
		$products = $this->all();
		foreach ( $products as $product ) {
			foreach ( $product['prices'] as $id => $price ) {
				if ( ! empty( $allowed ) && ! in_array( $id, $allowed, true ) ) {
					continue;
				}

				$list[] = array(
					'label' => $product['name'] . ' - ' . html_entity_decode( \WPUserManager\Stripe\Stripe::currencySymbol( $price['currency'] ) ) . number_format( $price['unit_amount'] / 100, 2 ),
					'value' => $id,
				);
			}
		}

		return $list;
	}

	/**
	 * @return int
	 */
	public function totalRecurringProducts() {
		$total    = 0;
		$products = $this->all();
		foreach ( $products as $product ) {
			if ( 'one_time' !== $product['type'] ) {
				++$total;
			}
		}

		return $total;
	}
}
