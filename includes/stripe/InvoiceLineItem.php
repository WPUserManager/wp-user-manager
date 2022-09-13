<?php

namespace WPUserManager\WPUMStripe;

use Carbon\Carbon;
use Stripe\InvoiceLineItem as StripeInvoiceLineItem;

class InvoiceLineItem {

	/**
	 * @var StripeInvoiceLineItem
	 */
	protected $item;

	/**
	 * InvoiceLineItem constructor.
	 *
	 * @param StripeInvoiceLineItem $item
	 */
	public function __construct( StripeInvoiceLineItem $item ) {
		$this->item = $item;
	}

	/**
	 * Dynamically access the Stripe invoice line item instance.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get( $key ) {
		return $this->item->{$key};
	}

	/**
	 * Get a human readable date for the start date.
	 *
	 * @return string
	 */
	public function startDate() {
		if ( $this->isSubscription() ) {
			return $this->startDateAsCarbon()->toFormattedDateString();
		}
	}

	/**
	 * Get a human readable date for the end date.
	 *
	 * @return string
	 */
	public function endDate() {
		if ( $this->isSubscription() ) {
			return $this->endDateAsCarbon()->toFormattedDateString();
		}
	}

	/**
	 * Get a Carbon instance for the start date.
	 *
	 * @return Carbon
	 */
	public function startDateAsCarbon() {
		if ( $this->isSubscription() ) {
			return Carbon::createFromTimestampUTC( $this->item->period->start );
		}
	}

	/**
	 * Get a Carbon instance for the end date.
	 *
	 * @return Carbon
	 */
	public function endDateAsCarbon() {
		if ( $this->isSubscription() ) {
			return Carbon::createFromTimestampUTC( $this->item->period->end );
		}
	}

	/**
	 * Determine if the invoice line item is for a subscription.
	 *
	 * @return bool
	 */
	public function isSubscription() {
		return $this->item->type === 'subscription';
	}

	/**
	 * Get the total for the invoice line item.
	 *
	 * @return string
	 */
	public function total() {
		return Invoice::formatCurrency( $this->item->amount, $this->item->currency );
	}
}
