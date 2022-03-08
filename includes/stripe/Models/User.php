<?php


namespace WPUserManager\WPUMStripe\Models;


use WPUserManager\WPUMStripe\Controllers\Subscriptions;

class User extends \WP_User {

	public $email;

	public $subscription;

	protected $product_data;

	/**
	 * User constructor.
	 *
	 * @param int    $id
	 * @param string $name
	 * @param string $site_id
	 */
	public function __construct( $id = 0, $name = '', $site_id = '' ) {
		parent::__construct( $id, $name, $site_id );

		$this->email = $this->user_email;

		$sub = ( new Subscriptions() )->where( 'user_id', $this->ID );

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
	public function isAdmin()
	{
		return $this->has_cap( 'administrator' );
	}

	/**
	 * Does the user have an active subscription?
	 *
	 * @return bool
	 */
	public function isSubscribed()
	{
		return $this->isAdmin() || ( $this->subscription && $this->subscription->active() );
	}

	protected function getProductData() {
		if ( $this->product_data ) {
			return $this->product_data;
		}

		$this->product_data = get_user_meta( $this->ID, 'wpum_stripe_plan', true );

		return $this->product_data;
	}

	public function shouldBeSubscribed() {
		$product_data = $this->getProductData();
		if ( ! $product_data ) {
			return false;
		}

		$product = new Product();
		$product->hydrate( $product_data );

		// TODO add filter so we check for roles

		if ( ! $product->is_recurring() ) {
			return false;
		}

		return true;
	}

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
}
