<?php
/**
 * Handles the Stripe Invoice
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

use WPUM\Dompdf\Dompdf;
use WPUM\Stripe\Customer;
use WPUserManager\Stripe\Models\User;

/**
 * Invoice
 */
class Invoice {

	/**
	 * @var \Stripe\Invoice
	 */
	protected $invoice;

	/**
	 * @var \WPUserManager\Stripe\Models\Invoice
	 */
	public $local_invoice;

	/**
	 * @var User
	 */
	public $customer;

	/**
	 * @var Customer
	 */
	protected $stripe_customer;

	/**
	 * Invoice constructor.
	 *
	 * @param \WPUM\Stripe\Invoice                 $invoice
	 * @param \WPUserManager\Stripe\Models\Invoice $local_invoice
	 */
	public function __construct( \WPUM\Stripe\Invoice $invoice, $local_invoice ) {
		$this->invoice       = $invoice;
		$this->local_invoice = $local_invoice;
		$this->customer      = $local_invoice->customer;
	}

	/**
	 * @return Customer
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function stripeCustomer() {
		if ( $this->stripe_customer ) {
			return $this->stripe_customer;
		}

		$this->stripe_customer = Customer::retrieve( $this->invoice->customer );

		return $this->stripe_customer;
	}

	/**
	 * @return string
	 */
	public function customerEmail() {
		return $this->customer->email;
	}

	/**
	 * @return string|null
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function customerName() {
		return $this->stripeCustomer()->name;
	}

	/**
	 * @return string
	 * @throws \Stripe\Exception\ApiErrorException
	 */
	public function customerAddress() {
		$address = $this->stripeCustomer()->address;

		$fields         = array( 'line1', 'line2', 'city', 'state', 'country', 'postal_code' );
		$address_string = '';
		foreach ( $fields as $field ) {
			if ( empty( $address->{$field} ) ) {
				continue;
			}
			$address_string .= $address->{$field} . "\n";

		}

		return $address_string;
	}

	/**
	 * Get the invoice ID.
	 *
	 * @return int
	 */
	public function id() {
		return (int) $this->local_invoice->id;
	}

	/**
	 * Get the invoice date.
	 *
	 * @return string
	 */
	public function date() {
		return mysql2date( __( 'F j, Y' ), $this->local_invoice->created_at );
	}

	/**
	 * Get the invoice total.
	 *
	 * @return string
	 */
	public function total() {
		return self::formatCurrency( $this->invoice->total, $this->local_invoice->currency );
	}

	/**
	 * Get the invoice line items.
	 *
	 * @return array
	 */
	public function lineItems() {
		$all_items = $this->invoice->lines->autoPagingIterator();

		$items = array();

		foreach ( $all_items as $item ) {
			$items[] = new InvoiceLineItem( $item );
		}

		return $items;
	}

	/**
	 * Determine if the invoice has a discount.
	 *
	 * @return bool
	 */
	public function hasDiscount() {
		return $this->rawDiscount() > 0;
	}

	/**
	 * Get the discount amount.
	 *
	 * @return string
	 */
	public function discount() {
		return self::formatCurrency( $this->rawDiscount(), $this->local_invoice->currency );
	}

	/**
	 * Get the raw discount amount.
	 *
	 * @return int
	 */
	public function rawDiscount() {
		if ( ! isset( $this->invoice->discount ) ) {
			return 0;
		}

		if ( $this->discountIsPercentage() ) {
			return (int) round( $this->invoice->subtotal * ( $this->percentOff() / 100 ) );
		}

		return $this->rawAmountOff();
	}

	/**
	 * Get the coupon code applied to the invoice.
	 *
	 * @return string|null
	 */
	public function coupon() {
		if ( isset( $this->invoice->discount ) ) {
			return $this->invoice->discount->coupon->name;
		}
	}

	/**
	 * Determine if the discount is a percentage.
	 *
	 * @return bool
	 */
	public function discountIsPercentage() {
		return isset( $this->invoice->discount ) && isset( $this->invoice->discount->coupon->percent_off );
	}

	/**
	 * Get the discount percentage for the invoice.
	 *
	 * @return int
	 */
	public function percentOff() {
		if ( $this->coupon() ) {
			return $this->invoice->discount->coupon->percent_off;
		}

		return 0;
	}

	/**
	 * Get the discount amount for the invoice.
	 *
	 * @return string
	 */
	public function amountOff() {
		return self::formatCurrency( $this->rawAmountOff(), $this->local_invoice->currency );
	}

	/**
	 * Get the raw discount amount for the invoice.
	 *
	 * @return int
	 */
	public function rawAmountOff() {
		if ( isset( $this->invoice->discount->coupon->amount_off ) ) {
			return $this->invoice->discount->coupon->amount_off;
		}

		return 0;
	}

	/**
	 * Download an invoice.
	 *
	 * @return mixed
	 */
	public function download() {
		$pdf  = new Dompdf();
		$date = mysql2date( __( 'Y-m-d' ), $this->local_invoice->created_at );

		ob_start();
		WPUM()->templates->set_template_data( apply_filters( 'wpum_stripe_invoice_data', array(
			'invoice'   => $this,
			'site_name' => get_bloginfo( 'name' ),
			'address'   => '',
		) ) )->get_template_part( 'stripe/invoice' );
		$html = ob_get_clean();
		$pdf->loadHtml( $html );
		$pdf->render();

		return $pdf->stream( "invoice-{$date}.pdf" );
	}

	/**
	 * Format currency.
	 *
	 * @param int    $amount
	 * @param string $currency
	 *
	 * @return string
	 */
	public static function formatCurrency( $amount, $currency ) {
		$formatter = new \NumberFormatter( 'en', \NumberFormatter::CURRENCY );

		return $formatter->formatCurrency( $amount / 100, strtoupper( $currency ) );
	}
}
