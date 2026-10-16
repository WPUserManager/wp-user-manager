<?php
/**
 * The Billing account tab shows invoice totals and plan prices to the cent,
 * instead of rounding them to whole units ($9.99 showed as $10).
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class BillingAmountsTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( 'WPUserManager\Stripe\Stripe' ) ) {
			$this->markTestSkipped( 'Stripe integration is not available.' );
		}
	}

	protected function render( $template, $data ) {
		ob_start();
		WPUM()->templates->set_template_data( $data )->get_template_part( $template );

		return html_entity_decode( ob_get_clean() );
	}

	public function test_invoice_total_keeps_cents() {
		$invoice = (object) array(
			'id'         => 1,
			'total'      => '9.99',
			'currency'   => 'usd',
			'created_at' => '2026-10-14 10:00:00',
		);

		$html = $this->render( 'stripe/account/invoices', array( 'invoices' => array( $invoice ) ) );

		$this->assertStringContainsString( '$9.99', $html );
	}

	public function test_plan_price_keeps_cents() {
		$products = array(
			array(
				'name'   => 'Basic',
				'prices' => array(
					'price_basic' => array(
						'unit_amount' => 999,
						'currency'    => 'usd',
						'recurring'   => array( 'interval' => 'month' ),
					),
				),
			),
		);

		$html = $this->render(
			'stripe/account/plans',
			array(
				'products'       => $products,
				'allowed_prices' => array( 'price_basic' ),
			)
		);

		$this->assertStringContainsString( '$9.99', $html );
		$this->assertStringContainsString( '/month', $html );
	}
}
