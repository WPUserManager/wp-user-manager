<?php
/**
 * Handles the Stripe webhook handler
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUM\Carbon\Carbon;
use WPUM\Stripe\Checkout\Session;
use WPUM\Stripe\Webhook;
use WPUM\Stripe\Stripe;
use WPUserManager\Stripe\Controllers\Invoices;
use WPUserManager\Stripe\Controllers\Subscriptions;
use WPUserManager\Stripe\Models\Product;
use WPUserManager\Stripe\Models\User;

/**
 * StripeWebhookController
 */
class StripeWebhookController {

	/**
	 * @var string
	 */
	protected $webhook_secret;

	/**
	 * @var Subscriptions
	 */
	protected $subscriptions;

	/**
	 * @var Invoices
	 */
	protected $invoices;

	/**
	 * StripeWebhookController constructor.
	 *
	 * @param string $secret_key
	 * @param string $webhook_secret
	 * @param string $gateway_mode
	 */
	public function __construct( $secret_key, $webhook_secret, $gateway_mode ) {
		if ( ! empty( $secret_key ) ) {
			Stripe::setApiKey( $secret_key );
		}

		$this->subscriptions  = new Subscriptions( $gateway_mode );
		$this->invoices       = new Invoices( $gateway_mode );
		$this->webhook_secret = $webhook_secret;
	}

	/**
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return \WP_REST_Response
	 */
	public function handleWebhook( \WP_REST_Request $request ) {
		$payload = json_decode( $request->get_body(), true );

		$method = 'handle' . $this->studly( str_replace( '.', '_', $payload['type'] ) );

		if ( ! $this->signatureIsValid( $request ) ) {
			return new \WP_REST_Response();
		}

		if ( method_exists( $this, $method ) ) {
			return $this->{$method}( $payload );
		}

		return new \WP_REST_Response();
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	protected function studly( $value ) {
		$value = ucwords( str_replace( array( '-', '_' ), ' ', $value ) );

		return str_replace( ' ', '', $value );
	}

	/**
	 * Verify that the request is from Stripe.
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
	protected function signatureIsValid( $request ) {
		$signature = $request->get_header( 'Stripe-Signature' );

		try {
			Webhook::constructEvent( $request->get_body(), $signature, $this->webhook_secret );
		} catch ( \Exception $exception ) {
			return false;
		}

		return true;
	}

	/**
	 * Handle the checkout.session.completed webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	protected function handleCheckoutSessionCompleted( $payload ) {
		if ( $payload['data']['object']['customer_email'] ) {
			$user    = get_user_by( 'email', $payload['data']['object']['customer_email'] );
			$user_id = $user->ID;
		} else {
			$subscription = $this->subscriptions->where( 'customer_id', $payload['data']['object']['customer'] );
			if ( ! $subscription ) {
				throw new \Exception( 'Subscription not found' );
			}
			$user_id = $subscription->user_id;
		}

		// For one time payments, mark as paid
		if ( ! isset( $payload['data']['object']['subscription'] ) ) {
			$user = new User( $user_id );
			$data = $user->getPlanMeta();

			$product = new Product();
			$product->hydrate( $data );
			$product->setPaid();

			$user->setPlanMeta( $product->to_array() );

			return new \WP_REST_Response( 'Webhook handled', 200 );
		}

		return $this->createSubscription( $user_id, $payload );
	}

	/**
	 * Handle the customer.subscription.created webhook.
	 * Fired when subscriptions are created via the Stripe dashboard
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	protected function handleCustomerSubscriptionCreated( $payload ) {
		$subscription = $this->subscriptions->where( 'subscription_id', $payload['data']['object']['id'] );
		if ( $subscription ) {
			// Subscription already exists, created by the checkout complete hook
			return new \WP_REST_Response( 'Webhook handled', 200 );
		}

		$customer_id = $payload['data']['object']['customer'];

		$subscription = $this->subscriptions->where( 'customer_id', $customer_id );
		if ( ! $subscription ) {
			throw new \Exception( 'Subscription not found' );
		}

		return $this->createSubscription( $subscription->user_id, $payload, false );
	}

	/**
	 * @param int   $user_id
	 * @param array $payload
	 * @param bool  $checkout
	 *
	 * @return \WP_REST_Response
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	protected function createSubscription( $user_id, $payload, $checkout = true ) {
		if ( $checkout ) {
			$session = Session::retrieve( array(
				'id'     => $payload['data']['object']['id'],
				'expand' => array( 'line_items' ),
			) );

			$stripePlan = $session->line_items->data[0]->price;

			$subscription_id = $payload['data']['object']['subscription'];
		} else {
			$stripePlan      = $payload['data']['object']['items']['data'][0]['plan'];
			$subscription_id = $payload['data']['object']['id'];
		}

		$subscription_data = array(
			'user_id'         => $user_id,
			'customer_id'     => $payload['data']['object']['customer'],
			'plan_id'         => $stripePlan['id'],
			'subscription_id' => $subscription_id,
			'trial_ends_at'   => null,
		);

		$this->subscriptions->insert( apply_filters( 'wpum_stripe_webhook_create_subscription_data', $subscription_data, $subscription_id, $stripePlan, $user_id, $payload ) );

		do_action( 'wpum_stripe_webhook_subscription_created', $user_id );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}

	/**
	 * Handle the customer.subscription.updated webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	protected function handleCustomerSubscriptionUpdated( $payload ) {
		$subscription = $this->subscriptions->where( 'subscription_id', $payload['data']['object']['id'] );

		if ( ! $subscription ) {
			throw new \Exception( 'Subscription not found' );
		}

		$subscription_data = array(
			'plan_id'       => $payload['data']['object']['plan']['id'],
			'trial_ends_at' => null,
			'ends_at'       => $payload['data']['object']['cancel_at_period_end'] ? Carbon::createFromTimestamp( $payload['data']['object']['current_period_end'] )->toDateTimeString() : null,
		);

		$this->subscriptions->update( $subscription->id, apply_filters( 'wpum_stripe_webhook_update_subscription_data', $subscription_data, $subscription->id, $payload ) );

		do_action( 'wpum_stripe_webhook_subscription_updated', $subscription );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}

	/**
	 * Handle the customer.subscription.deleted webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	protected function handleCustomerSubscriptionDeleted( $payload ) {
		$subscription = $this->subscriptions->where( 'subscription_id', $payload['data']['object']['id'] );
		if ( ! $subscription ) {
			throw new \Exception( 'Subscription not found' );
		}

		$this->subscriptions->update( $subscription->id, array(
			'ends_at' => current_time( 'mysql' ),
		) );

		do_action( 'wpum_stripe_webhook_subscription_deleted', $subscription );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}

	/**
	 * Handle the invoice.payment_succeeded webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 * @throws \Exception
	 */
	protected function handleInvoicePaymentSucceeded( $payload ) {
		$subscription = $this->subscriptions->where( 'customer_id', $payload['data']['object']['customer'] );
		if ( ! $subscription ) {
			throw new \Exception( 'Subscription not found' );
		}

		$invoice = $this->invoices->where( 'invoice_id', $payload['data']['object']['id'] );

		$total      = $payload['data']['object']['total'] / 100;
		$currency   = $payload['data']['object']['currency'];
		$created_at = Carbon::createFromTimestamp( $payload['data']['object']['created'] )->toDateTimeString();

		if ( $invoice ) {
			$this->invoices->update( $invoice->id, array(
				'total'      => $total,
				'currency'   => $currency,
				'created_at' => $created_at,
			) );
		} else {
			$this->invoices->insert( array(
				'user_id'    => $subscription->user_id,
				'invoice_id' => $payload['data']['object']['id'],
				'total'      => $total,
				'currency'   => $currency,
				'created_at' => $created_at,
			) );
		}

		do_action( 'wpum_stripe_webhook_invoice_created', $subscription );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}
}
