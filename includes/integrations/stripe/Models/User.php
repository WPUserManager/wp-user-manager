<?php
/**
 * Handles the User
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe\Models;

use WPUserManager\Stripe\Controllers\Subscriptions;

/**
 * User
 */
class User extends \WP_User {

	/**
	 * @var string
	 */
	public $email;

	/**
	 * @var Subscription|null
	 */
	public $subscription;

	/**
	 * @var mixed
	 */
	protected $product_data;

	/**
	 * @var string
	 */
	protected $gateway_mode;

	/**
	 * User constructor.
	 *
	 * @param int    $id
	 * @param string $name
	 * @param string $site_id
	 */
	public function __construct( $id = 0, $name = '', $site_id = '' ) {
		parent::__construct( $id, $name, $site_id );

		$this->gateway_mode = wpum_get_option( 'stripe_gateway_mode', 'test' );
		$this->email        = $this->user_email;

		$sub = ( new Subscriptions( $this->gateway_mode ) )->where( 'user_id', $this->ID );

		if ( $sub ) {
			$sub = new Subscription( $sub );
		}

		$this->subscription = $sub;
	}

	/**
	 * Does the user have an active subscription?
	 *
	 * @return bool
	 */
	public function isAdmin() {
		 return $this->has_cap( 'administrator' );
	}

	/**
	 * Does the user have an active subscription?
	 *
	 * @return bool
	 */
	public function isSubscribed() {
		return $this->isAdmin() || ( $this->subscription && $this->subscription->active() );
	}

	/**
	 * @return array
	 */
	public function getPlanMeta() {
		return get_user_meta( $this->ID, 'wpum_stripe_plan_' . $this->gateway_mode, true );
	}

	/**
	 * @return false|\WPUM_Registration_Form
	 */
	public function getFormRegisteredWith() {
		$form_id = get_user_meta( $this->ID, 'wpum_form_id', true );
		if ( ! $form_id ) {
			return false;
		}

		return new \WPUM_Registration_Form( $form_id );
	}

	/**
	 * @param array $data
	 *
	 * @return bool|int
	 */
	public function setPlanMeta( $data ) {
		return update_user_meta( $this->ID, 'wpum_stripe_plan_' . $this->gateway_mode, $data );
	}

	/**
	 * @return array
	 */
	protected function getProductData() {
		if ( $this->product_data ) {
			return $this->product_data;
		}

		$this->product_data = $this->getPlanMeta();

		return $this->product_data;
	}

	/**
	 * @return bool
	 */
	public function shouldBeSubscribed() {
		$product_data = $this->getProductData();
		if ( ! $product_data ) {
			return false;
		}

		$product = new Product();
		$product->hydrate( $product_data );

		$shouldBeSubscribed = true;

		if ( ! $product->is_recurring() ) {
			$shouldBeSubscribed = false;
		}

		return apply_filters( 'wpum_stripe_user_should_be_subscribed', $shouldBeSubscribed, $this, $product );
	}

	/**
	 * @return bool|mixed
	 */
	public function isPaid() {
		if ( $this->isAdmin() ) {
			return true;
		}

		$product_data = $this->getProductData();
		if ( ! $product_data ) {
			return true;
		}

		return $product_data['paid'];
	}

	/**
	 * @param string $plan
	 *
	 * @return bool
	 */
	public function hasPaidByPlan( $plan ) {
		if ( $this->subscription && $plan === $this->subscription->plan_id ) {
			return true;
		}

		$purchased = $this->getProductData();

		if ( is_array( $purchased ) && $plan === $purchased['id'] ) {
			return true;
		}

		return false;
	}
}
