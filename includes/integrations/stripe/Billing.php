<?php
/**
 * Handles the Stripe billing
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUM\Stripe\BillingPortal\Session as PortalSession;
use WPUM\Stripe\Stripe;
use WPUserManager\Stripe\Controllers\Products;
use WPUserManager\Stripe\Models\User;

/**
 * Billing
 */
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
	 * @param Products $products
	 * @param string   $connect_url
	 */
	public function __construct( $products, $connect_url ) {
		$this->products    = $products;
		$this->connect_url = $connect_url;
	}

	/**
	 * @return string
	 */
	public function getBillingURL() {
		if ( empty( $this->billing_url ) ) {
			$this->billing_url = \WPUserManager\Stripe\Stripe::getBillingURL();
		}

		return $this->billing_url;
	}

	/**
	 * @param string      $test_mode
	 * @param User        $user
	 * @param string      $plan
	 * @param string      $returnUrl
	 * @param null|string $stripe_account_id
	 *
	 * @return array
	 */
	protected function prepareData( $test_mode, $user, $plan, $returnUrl, $stripe_account_id = null ) {
		$data = array(
			'test_mode'   => (int) $test_mode,
			'plan'        => $plan,
			'success_url' => $returnUrl,
			'cancel_url'  => $this->getBillingURL(),
			'user_id'     => $user->ID,
		);

		if ( $stripe_account_id ) {
			$data['stripe_account_id'] = $stripe_account_id;
		}

		if ( $user->subscription && $user->subscription->customer_id ) {
			$data['customer'] = $user->subscription->customer_id;
		} else {
			$data['customer_email'] = rawurlencode( $user->email );
		}

		$product = $this->products->get_by_plan( $plan );
		if ( ! $product->is_recurring() ) {
			$data['amount'] = $product->amount;
		}

		return $data;
	}

	/**
	 * Create a Stripe Checkout session.
	 *
	 * @param bool        $test_mode
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

		$data = $this->prepareData( $test_mode, $user, $plan, $returnUrl, $stripe_account_id );

		$connect = new Connect();
		$mode    = $test_mode ? 'test' : 'live';

		$response = wp_remote_get( $this->getCheckoutRequestURL( $data, $connect->get_stripe_secret( $mode ) ) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_array( $body ) && ! empty( $body['error'] ) && is_string( $body['error'] ) ) {
				$connect->record_checkout_error( $mode, $body['error'] );
			}

			return false;
		}

		// The Connect service accepted the request but couldn't verify its signature.
		$connect->record_checkout_error( $mode, (string) wp_remote_retrieve_header( $response, 'x-wpum-connect-signature' ) );

		if ( is_array( $body ) && isset( $body['id'] ) ) {
			return $body['id'];
		}

		return false;
	}

	/**
	 * The Connect service URL that creates a Checkout Session, signed with the account's secret key.
	 *
	 * @param array  $data   Request data from prepareData().
	 * @param string $secret The connected account's secret key. It is never sent.
	 *
	 * @return string
	 */
	public function getCheckoutRequestURL( $data, $secret ) {
		// prepareData() encodes the email for a query string; sign the value the Connect service reads.
		if ( isset( $data['customer_email'] ) ) {
			$data['customer_email'] = rawurldecode( $data['customer_email'] );
		}

		$params = self::signConnectRequest( $data, $secret );

		return $this->connect_url . '/checkout?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Add a timestamp and an HMAC-SHA256 signature to a Connect service request.
	 *
	 * The signature covers every parameter, so the Connect service can check the
	 * request came from a site holding the connected account's key.
	 *
	 * @param array    $params    Request parameters.
	 * @param string   $secret    The connected account's secret key.
	 * @param null|int $timestamp Defaults to now.
	 *
	 * @return array The parameters to send, as strings. Unsigned if there is no secret.
	 */
	public static function signConnectRequest( $params, $secret, $timestamp = null ) {
		$params = self::normaliseConnectParams( $params );
		unset( $params['signature'] );

		if ( empty( $secret ) || ! is_string( $secret ) ) {
			return $params;
		}

		$params['timestamp'] = (string) ( null === $timestamp ? time() : (int) $timestamp );
		$params['signature'] = hash_hmac( 'sha256', self::canonicalConnectRequest( $params ), $secret );

		return $params;
	}

	/**
	 * The string a Connect service request signature is made from: every parameter
	 * except the signature, sorted by key, as rawurlencoded key=value pairs joined with '&'.
	 *
	 * @param array $params
	 *
	 * @return string
	 */
	public static function canonicalConnectRequest( $params ) {
		$params = self::normaliseConnectParams( $params );
		unset( $params['signature'] );
		ksort( $params, SORT_STRING );

		$pairs = array();
		foreach ( $params as $key => $value ) {
			$pairs[] = rawurlencode( (string) $key ) . '=' . rawurlencode( $value );
		}

		return implode( '&', $pairs );
	}

	/**
	 * Parameters as the Connect service receives them: strings, without the nulls
	 * and nested values a query string can't carry.
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	protected static function normaliseConnectParams( $params ) {
		$normalised = array();

		foreach ( (array) $params as $key => $value ) {
			if ( null === $value || is_array( $value ) || is_object( $value ) ) {
				continue;
			}

			if ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}

			$normalised[ $key ] = (string) $value;
		}

		return $normalised;
	}

	/**
	 * Create a Stripe Portal session.
	 *
	 * @param string      $secret
	 * @param string      $customer_id
	 * @param null|string $returnUrl
	 *
	 * @return PortalSession|false
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function createStripePortalSession( $secret, $customer_id, $returnUrl = null ) {
		Stripe::setApiKey( $secret );

		if ( is_null( $returnUrl ) ) {
			$returnUrl = $this->getBillingURL();
		}

		try {
			return PortalSession::create( array(
				'customer'   => $customer_id,
				'return_url' => $returnUrl,
			) );
		} catch ( \Stripe\Exception\ApiErrorException $exception ) {
			return false;
		}
	}
}
