<?php


namespace WPUserManager\WPUMStripe;

use Stripe\BillingPortal\Session as PortalSession;
use Stripe\Stripe;
use WPUserManager\WPUMStripe\Controllers\Products;
use WPUserManager\WPUMStripe\Models\User;

class Billing {

	/**
	 * @var Products
	 */
	protected $products;

	/**
	 * @var string
	 */
	protected $connect_url;

	/**
	 * @var string
	 */
	protected $billing_url;

	/**
	 * @param Products$products
	 * @param string $connect_url
	 */
	public function __construct( $products, $connect_url ) {
		$this->products = $products;
		$this->connect_url = $connect_url;
	}

	public function getBillingURL() {
		if ( empty( $this->billing_url ) ) {
			$this->billing_url = \WPUserManager\WPUMStripe\Stripe::getBillingURL();
		}

		return $this->billing_url;
	}

	/**
	 * Create a Stripe Checkout session.
	 *
	 * @param bool            $test_mode
	 * @param User        $user
	 * @param string      $plan
	 * @param null|string $returnUrl
	 *
	 * @return string|false
	 */
	public function createStripeCheckoutSession( $test_mode, $user, $plan, $returnUrl = null ) {
		if ( is_null( $returnUrl ) ) {
			$returnUrl = $this->getBillingURL();
		}

		$stripe_account_id = wpum_get_option( 'stripe_connect_account_id' );
		if ( ! $stripe_account_id ) {
			return false;
		}

		$data = [
			'stripe_account_id' => $stripe_account_id,
			'test_mode'         => (int) $test_mode,
			'plan'              => $plan,
			'success_url'       => $returnUrl,
			'cancel_url'        => $this->getBillingURL(),
		];

		if ( $user->subscription && $user->subscription->customer_id ) {
			$data['customer'] = $user->subscription->customer_id;
		} else {
			$data['customer_email'] = $user->email;
		}

		$product = $this->products->get_by_plan( $plan );
		if ( ! $product->is_recurring() ) {
			$data['amount'] = $product->amount;
		}

		$wpum_checkout_url = add_query_arg( $data, $this->connect_url . '/checkout' );

		$response = wp_remote_get( esc_url_raw( $wpum_checkout_url ) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$data = json_decode( $response['body'], true );

		if ( isset( $data['id'])) {
			return $data['id'];
		}

		return false;
	}

	/**
	 * Create a Stripe Portal session.
	 *
	 * @param string      $secret
	 * @param string      $customer_id
	 * @param null|string $returnUrl
	 *
	 * @return PortalSession
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function createStripePortalSession( $secret, $customer_id, $returnUrl = null ) {
		Stripe::setApiKey( $secret );

		if ( is_null( $returnUrl ) ) {
			$returnUrl = $this->getBillingURL();
		}

		return PortalSession::create( [
			'customer'   => $customer_id,
			'return_url' => $returnUrl,
		] );
	}
}
