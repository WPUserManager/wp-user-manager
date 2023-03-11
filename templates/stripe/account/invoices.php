<?php
/*
 * The Template for the Account page invoices section
 *
 * This template can be overridden by copying it to yourtheme/wpum/stripe/account/invoices.php
 *
 * @version 2.9.0
 */
?>


<div class="wpum-form" style="margin-top: 2rem;">
	<h3><?php _e( 'Invoices', 'wp-user-manager' ); ?></h3>
	<?php if ( empty( $data->invoices ) ) : ?>
	<p><?php _e( 'You don\'t have any invoices yet.', 'wp-user-manager' ); ?> </p>
</div>
<?php return;
endif; ?>

<table class="table mb-0">
	<tbody>
	<?php
	foreach ( $data->invoices as $invoice ) :
		if ( $invoice->total <= 0 ) {
			continue;
		}
		?>
		<tr>
			<td class="">
				<?php echo mysql2date( __( apply_filters( 'wpum_stripe_invoice_date_format', 'F j, Y' ) ), $invoice->created_at ); ?>
			</td>
			<td class="">
				<?php echo \WPUserManager\Stripe\Stripe::currencySymbol( $invoice->currency ); ?><?php echo number_format( $invoice->total ); ?>
			</td>
			<td class="text-right">
				<a href="<?php echo get_permalink( wpum_get_core_page_id( 'account' ) ) . '/billing/?invoice_id=' . $invoice->id; ?>">
					<?php _e( 'Download', 'wp-user-manager' ); ?>
				</a>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
</div>
