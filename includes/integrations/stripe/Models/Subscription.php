<?php
/**
 * Handles the Stripe subscription
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe\Models;

/**
 * Subscription
 */
class Subscription {

	/**
	 * @var int
	 */
	public $id;

	/**
	 * @var string
	 */
	public $plan_id;

	/**
	 * @var string
	 */
	public $subscription_id;

	/**
	 * @var string
	 */
	public $customer_id;

	/**
	 * @var int
	 */
	public $user_id;

	/**
	 * @var string
	 */
	public $ends_at;

	/**
	 * @var string
	 */
	public $trial_ends_at;

	/**
	 * Subscription constructor.
	 *
	 * @param array $data
	 */
	public function __construct( $data ) {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Determine if the subscription is active.
	 *
	 * @return bool
	 */
	public function active() {
		return is_null( $this->ends_at ) || $this->onGracePeriod();
	}

	/**
	 * Determine if the subscription is in a trial period.
	 *
	 * @return bool
	 */
	public function onTrial() {
		 return $this->trial_ends_at && $this->trial_ends_at > current_time( 'mysql' );
	}

	/**
	 * Determine if the subscription is in a grace period.
	 *
	 * @return bool
	 */
	public function onGracePeriod() {
		return $this->cancelled() && $this->ends_at > current_time( 'mysql' );
	}

	/**
	 * @return bool
	 */
	public function cancelled() {
		return ! is_null( $this->ends_at );
	}

	/**
	 * @return bool
	 */
	public function expired() {
		 return $this->cancelled() && ! $this->onGracePeriod();
	}

	/**
	 * @return string
	 */
	public function status() {
		if ( $this->onGracePeriod() ) {
			return 'cancelled';
		}
		if ( $this->expired() ) {
			return 'expired';
		}

		return 'active';
	}
}
