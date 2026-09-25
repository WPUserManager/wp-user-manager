<?php
/**
 * Invoice PDFs are rendered with the scoped dompdf in vendor-dist. dompdf
 * builds frame class names from strings, which php-scoper can't prefix, so
 * this fataled with "Class Dompdf\FrameDecorator\Block not found" until the
 * patchers in scoper.inc.php fixed them.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class InvoicePdfTest extends WPUMTestCase {

	public function test_scoped_dompdf_renders_invoice_style_html() {
		if ( ! class_exists( 'WPUM\Dompdf\Dompdf' ) ) {
			$this->markTestSkipped( 'Scoped dompdf is not available.' );
		}

		// Same font as templates/stripe/invoice.php; only Helvetica ships in vendor-dist.
		$html = '<html><head><style>body { font-family: Arial, sans-serif; }</style></head><body>'
			. '<div><strong>Site</strong> <span>Invoice #: 1</span></div>'
			. '<table><thead><tr><th>Item</th><th>Amount</th></tr></thead>'
			. '<tbody><tr><td>1 × Plan</td><td>$9.99</td></tr></tbody></table>'
			. '<ul><li>Line</li></ul>'
			. '</body></html>';

		$pdf = new \WPUM\Dompdf\Dompdf();
		$pdf->loadHtml( $html );
		$pdf->render();

		$this->assertStringStartsWith( '%PDF-', $pdf->output() );
	}
}
