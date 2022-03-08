<?php

namespace WPUserManager\WPUMStripe;

use Carbon\Carbon;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Stripe\Stripe;
use WPUserManager\WPUMStripe\Controllers\Invoices;
use WPUserManager\WPUMStripe\Controllers\Subscriptions;
use WPUserManager\WPUMStripe\Models\Product;

class StripeWebhookController {

	protected $webhook_secret;

	protected $subscriptions;
	protected $invoices;

	/**
	 * StripeWebhookController constructor.
	 *
	 * @param $secret_key
	 * @param $webhook_secret
	 */
	public function __construct( $secret_key, $webhook_secret ) {
		if ( ! empty( $secret_key ) ) {
			Stripe::setApiKey( $secret_key );
		}

		$this->subscriptions = new Subscriptions();
		$this->invoices = new Invoices();
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

	protected function studly( $value ) {
		$value = ucwords( str_replace( [ '-', '_' ], ' ', $value ) );

		return str_replace( ' ', '', $value );
	}

	/**
	 * Verify that the request is from Stripe.
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
	 */
	protected function handleCheckoutSessionCompleted( $payload ) {
		if ( $payload['data']['object']['customer_email'] ) {
			$user    = get_user_by_email( $payload['data']['object']['customer_email'] );
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
			$data    = get_user_meta( $user_id, 'wpum_stripe_plan', true );
			$product = new Product();
			$product->hydrate( $data );
			$product->setPaid();

			update_user_meta( $user_id, 'wpum_stripe_plan', $product->to_array() );

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
	 * @param       $user_id
	 * @param array $payload
	 * @param bool  $checkout
	 *
	 * @return \WP_REST_Response
	 */
	protected function createSubscription( $user_id, $payload, $checkout = true ) {
		if ( $checkout ) {
			$session = Session::retrieve( [
				'id'     => $payload['data']['object']['id'],
				'expand' => [ 'line_items' ],
			] );

			$stripePlan = $session->line_items->data[0]->price;

			$subscription_id = $payload['data']['object']['subscription'];
		} else {
			$stripePlan      = $payload['data']['object']['items']['data'][0]['plan'];
			$subscription_id = $payload['data']['object']['id'];
		}

		//$plan = Subscription::getPlan( $stripePlan['id'] );
		// TODO
		//$trialPeriodDays = isset( $plan['trial'] ) ? $plan['trial'] : 0;
		$trialPeriodDays = 0;
		$trialEndsAt = null;
		//$trialEndsAt = $trialPeriodDays > 0 ? now()->addDays( $trialPeriodDays ) : null;

		$this->subscriptions->insert( [
			'user_id'         => $user_id,
			'customer_id'     => $payload['data']['object']['customer'],
			'plan_id'         => $stripePlan['id'],
			'subscription_id' => $subscription_id,
			'trial_ends_at'   => $trialEndsAt,
		] );


		do_action( 'wpum_stripe_webhook_subscription_created', $user_id );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}

	/**
	 * Handle the customer.subscription.updated webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 */
	protected function handleCustomerSubscriptionUpdated( $payload ) {
		$subscription = $this->subscriptions->where( 'subscription_id', $payload['data']['object']['id'] );

		if ( ! $subscription ) {
			throw new \Exception('Subscription not found');
		}

		$trialEnd = $payload['data']['object']['trial_end'];

		// TODO
		//$trialEndsAt = $trialEnd ? Carbon::createFromTimestamp( $trialEnd )->toDateTimeString() : null;
		$trialEndsAt = null;

		$this->subscriptions->update( $subscription->id, [
			'plan_id'       => $payload['data']['object']['plan']['id'],
			'trial_ends_at' => $trialEndsAt,
			'ends_at'       => $payload['data']['object']['cancel_at_period_end'] ? Carbon::createFromTimestamp( $payload['data']['object']['current_period_end'] )->toDateTimeString() : null,
		] );

		do_action( 'wpum_stripe_webhook_subscription_updated', $subscription );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}

	/**
	 * Handle the customer.subscription.deleted webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 */
	protected function handleCustomerSubscriptionDeleted( $payload ) {
		$subscription = $this->subscriptions->where( 'subscription_id', $payload['data']['object']['id'] );
		if ( ! $subscription ) {
			throw new \Exception( 'Subscription not found' );
		}

		$this->subscriptions->update( $subscription->id, [
			'ends_at' => current_time('mysql'),
		] );

		do_action( 'wpum_stripe_webhook_subscription_deleted', $subscription );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}

	/**
	 * Handle the invoice.payment_succeeded webhook.
	 *
	 * @param array $payload
	 *
	 * @return \WP_REST_Response
	 */
	protected function handleInvoicePaymentSucceeded( $payload ) {
		error_log( print_r( $payload['data']['object'], true ) );
		$subscription = $this->subscriptions->where( 'customer_id', $payload['data']['object']['customer'] );
		if ( ! $subscription ) {
			throw new \Exception( 'Subscription not found' );
		}

		$invoice = $this->invoices->where( 'invoice_id', $payload['data']['object']['id'] );

		$total = $payload['data']['object']['total'] / 100;
		$currency = $payload['data']['object']['currency'];
		$created_at = Carbon::createFromTimestamp( $payload['data']['object']['created'] )->toDateTimeString();

		if ( $invoice ) {
			$this->invoices->update( $invoice->id, [
				'total'      => $total,
				'currency'   => $currency,
				'created_at' => $created_at,
			] );
		} else {
			$this->invoices->insert( [
				'user_id'    => $subscription->user_id,
				'invoice_id' => $payload['data']['object']['id'],
				'total'      => $total,
				'currency'   => $currency,
				'created_at' => $created_at,
			] );
		}

		do_action( 'wpum_stripe_webhook_invoice_created', $subscription );

		return new \WP_REST_Response( 'Webhook handled', 200 );
	}
}
