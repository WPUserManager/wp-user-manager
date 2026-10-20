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
		return $this->isAdmin() || $this->hasActiveAllowedSubscription();
	}

	/**
	 * Is the subscription active, and for a plan this user may pay for?
	 * A subscription switched to another plan, such as a cheaper one from
	 * another registration form, doesn't count until it's switched back.
	 *
	 * @return bool
	 */
	protected function hasActiveAllowedSubscription() {
		if ( ! $this->subscription || ! $this->subscription->active() ) {
			return false;
		}

		return $this->isSubscriptionPlanAllowed( (string) $this->subscription->plan_id );
	}

	/**
	 * Can a subscription to this price count? Either it's one of the user's plans, or
	 * they've upgraded in the Stripe customer portal: an eligible price that costs at
	 * least as much as the plan they registered for.
	 *
	 * @param string $plan_id
	 *
	 * @return bool
	 */
	public function isSubscriptionPlanAllowed( $plan_id ) {
		return $this->isPlanAllowed( $plan_id ) || $this->isUpgradePlan( $plan_id );
	}

	/**
	 * Is this price an upgrade on the plan the user registered for? It must be one of the
	 * Eligible Products in the Stripe settings, if any are set, in the same currency, and
	 * cost at least as much per day.
	 *
	 * @param string $plan_id
	 *
	 * @return bool
	 */
	public function isUpgradePlan( $plan_id ) {
		$product_data = $this->getProductData();
		if ( ! is_array( $product_data ) || empty( $product_data['id'] ) || '' === (string) $plan_id ) {
			return false;
		}

		$eligible = array_filter( (array) wpum_get_option( $this->gateway_mode . '_stripe_products', array() ) );
		if ( ! empty( $eligible ) && ! in_array( (string) $plan_id, array_map( 'strval', $eligible ), true ) ) {
			return false;
		}

		$registered = $this->getCachedPrice( (string) $product_data['id'] );
		$price      = $this->getCachedPrice( (string) $plan_id );
		if ( ! $registered || ! $price || strtolower( (string) $registered['currency'] ) !== strtolower( (string) $price['currency'] ) ) {
			return false;
		}

		$registered_daily = $this->dailyAmount( $registered );
		$daily            = $this->dailyAmount( $price );
		if ( null === $registered_daily || null === $daily ) {
			return false;
		}

		return apply_filters( 'wpum_stripe_user_is_upgrade_plan', $daily >= $registered_daily, $plan_id, $this );
	}

	/**
	 * A price from the cached Stripe product list. Never calls Stripe, since this runs in
	 * capability checks.
	 *
	 * @param string $price_id
	 *
	 * @return array|null
	 */
	protected function getCachedPrice( $price_id ) {
		$cache_key = 'wpum_' . $this->gateway_mode . '_stripe_products';

		$products = get_transient( $cache_key );
		if ( ! is_array( $products ) ) {
			$products = get_option( $cache_key . '_last_good', array() );
		}

		foreach ( (array) $products as $product ) {
			if ( isset( $product['prices'][ $price_id ] ) && is_array( $product['prices'][ $price_id ] ) ) {
				return $product['prices'][ $price_id ];
			}
		}

		return null;
	}

	/**
	 * A recurring price's cost per day, so monthly and annual prices compare fairly.
	 *
	 * @param array $price
	 *
	 * @return float|null
	 */
	protected function dailyAmount( $price ) {
		if ( ! isset( $price['unit_amount'], $price['currency'], $price['recurring']['interval'] ) ) {
			return null;
		}

		$days = array(
			'day'   => 1,
			'week'  => 7,
			'month' => 365.25 / 12,
			'year'  => 365.25,
		);

		$interval = $price['recurring']['interval'];
		$count    = isset( $price['recurring']['interval_count'] ) ? max( 1, (int) $price['recurring']['interval_count'] ) : 1;
		if ( ! isset( $days[ $interval ] ) ) {
			return null;
		}

		return (float) $price['unit_amount'] / ( $days[ $interval ] * $count );
	}

	/**
	 * Is the subscription active, but for a plan this user may not pay for?
	 *
	 * @return bool
	 */
	public function hasSubscriptionOnDisallowedPlan() {
		return $this->subscription && $this->subscription->active() && ! $this->hasActiveAllowedSubscription();
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
			// Registered through a paid form but no plan was recorded: fail closed.
			// Members without the marker predate this check and keep their access.
			return ! get_user_meta( $this->ID, 'wpum_stripe_payment_required', true );
		}

		return $product_data['paid'];
	}

	/**
	 * The Stripe price IDs this user may pay for: their stored plan and the
	 * plans on the registration form they used. Not the subscription's plan,
	 * which the customer can change in the Stripe customer portal.
	 *
	 * @return array
	 */
	public function getAllowedPlanIds() {
		$plan_ids = array();

		$product_data = $this->getProductData();
		if ( is_array( $product_data ) && ! empty( $product_data['id'] ) ) {
			$plan_ids[] = (string) $product_data['id'];
		}

		$form = $this->getFormRegisteredWith();
		if ( $form && $form->exists() ) {
			$plan_ids = array_merge( $plan_ids, array_map( 'strval', array_filter( (array) $form->get_setting( 'stripe_plan_id' ) ) ) );
		}

		return apply_filters( 'wpum_stripe_user_allowed_plan_ids', array_values( array_unique( $plan_ids ) ), $this );
	}

	/**
	 * Can a payment for this price count towards the user's plan?
	 * Users with no plan to pay for aren't limited.
	 *
	 * @param string $plan_id
	 *
	 * @return bool
	 */
	public function isPlanAllowed( $plan_id ) {
		$allowed = $this->getAllowedPlanIds();
		if ( empty( $allowed ) ) {
			return ! get_user_meta( $this->ID, 'wpum_stripe_payment_required', true );
		}

		return in_array( (string) $plan_id, $allowed, true );
	}

	/**
	 * @param string $plan
	 *
	 * @return bool
	 */
	public function hasPaidByPlan( $plan ) {
		if ( $this->subscription && $plan === $this->subscription->plan_id && $this->hasActiveAllowedSubscription() ) {
			return true;
		}

		$purchased = $this->getProductData();

		if ( is_array( $purchased ) && isset( $purchased['id'] ) && $plan === $purchased['id'] && ! empty( $purchased['paid'] ) ) {
			return true;
		}

		return false;
	}
}
