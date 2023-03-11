<?php
/**
 * The Template for the Stripe invoice
 *
 * This template can be overridden by copying it to yourtheme/wpum/stripe/invoice.php
 *
 * @version 2.9.0
 */

?>
<!doctype html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title><?php echo esc_html( $data->site_name ); ?><?php echo esc_html( __( 'Invoice', 'wp-user-manager' ) ); ?></title>

	<style>
		body {
			background: #fff;
		}

		.invoice-box {
			font-size: 16px;
			line-height: 24px;
			font-family: Arial, sans-serif;
			color: #555;
		}

		.invoice-box table {
			width: 100%;
			line-height: inherit;
			text-align: left;
		}

		.invoice-box table td {
			padding: 5px;
			vertical-align: top;
		}

		.invoice-box table tr td:nth-child(2) {
			text-align: right;
		}

		.invoice-box table tr.top table td {
			padding-bottom: 20px;
		}

		.invoice-box table tr.top table td.title img {
			display: block;
			padding: 10px 20px;
			width: 200px;
			background-color: #6c2bd9;
		}

		.invoice-box table tr.information table td {
			padding-bottom: 40px;
		}

		.invoice-box table tr.heading td {
			background: #eee;
			border-bottom: 1px solid #ddd;
			font-weight: bold;
		}

		.invoice-box table tr.details td {
			padding-bottom: 20px;
		}

		.invoice-box table tr.item td {
			border-bottom: 1px solid #eee;
		}

		.invoice-box table tr.item.last td {
			border-bottom: none;
		}

		.invoice-box table tr.item .item-date {
			font-size: 12px;
		}

		.invoice-box table tr.total td:nth-child(2) {
			font-weight: bold;
		}

		@media only screen and (max-width: 600px) {
			.invoice-box table tr.top table td {
				width: 100%;
				display: block;
				text-align: center;
			}

			.invoice-box table tr.information table td {
				width: 100%;
				display: block;
				text-align: center;
			}
		}
	</style>
</head>

<body>
<div class="invoice-box">
	<table cellpadding="0" cellspacing="0">
		<tr class="top">
			<td colspan="2">
				<table>
					<tr>
						<td class="title">
							<strong><?php echo esc_html( $data->site_name ); ?></strong><br>
							<?php echo esc_html( $data->address ); ?>
						</td>

						<td>
							Invoice #: <?php echo esc_html( $data->invoice->id() ); ?><br>
							Date: <?php echo esc_html( $data->invoice->date() ); ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>

		<tr class="information">
			<td colspan="2">
				<table>
					<tr>
						<td>
						</td>

						<td>
							<?php echo esc_html( $data->invoice->customerEmail() ); ?><br>
							<?php echo esc_html( $data->invoice->customerName() ); ?><br>
							<?php echo nl2br( $data->invoice->customerAddress() ); // phpcs:ignore ?>
						</td>
					</tr>
				</table>
			</td>
		</tr>

		<tr class="heading">
			<td>
				Item
			</td>

			<td>
				Amount
			</td>
		</tr>

		<?php foreach ( $data->invoice->lineItems() as $data->item ) : ?>
			<tr class="item">
				<td>
					<?php echo esc_html( $data->item->description ); ?><br>

					<?php if ( $data->item->isSubscription() ) : ?>
						<span class="item-date">
							<?php echo esc_html( $data->item->startDateAsCarbon()->formatLocalized( '%B %e, %Y' ) ); ?> -
							<?php echo esc_html( $data->item->endDateAsCarbon()->formatLocalized( '%B %e, %Y' ) ); ?>
						</span>
					<?php endif; ?>
				</td>

				<td>
					<?php echo esc_html( $data->item->total() ); ?>
			</tr>
		<?php endforeach; ?>

		<?php if ( $data->invoice->hasDiscount() ) : ?>
			<tr class="item">
				<td>
					<?php if ( $data->invoice->discountIsPercentage() ) : ?>
						<?php echo esc_html( $data->invoice->coupon() ); ?> (<?php echo esc_html( $data->invoice->percentOff() ); ?>% Off)
					<?php else : ?>
						<?php echo esc_html( $data->invoice->coupon() ); ?> (<?php echo esc_html( $data->invoice->amountOff() ); ?> Off)
					<?php endif; ?>
				</td>

				<td>-<?php echo esc_html( $data->invoice->discount() ); ?></td>
			</tr>
		<?php endif; ?>

		<tr class="total">
			<td>
				<?php echo esc_html( $data->invoice->total() ); ?>
			</td>
		</tr>
		<tr>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td colspan="2"></td>
		</tr>
		<tr>
			<td colspan="2"></td>
		</tr>
	</table>
</div>
</body>
</html>
