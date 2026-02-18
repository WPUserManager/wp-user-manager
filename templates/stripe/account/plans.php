<?php
/**
 * The Template for the Account page plans section
 *
 * This template can be overridden by copying it to yourtheme/wpum/stripe/account/plans.php
 *
 * @version 2.9.0
 */

?>

<h4><?php echo esc_html( apply_filters( 'wpum_stripe_account_billing_plan_header', __( 'Select Plan', 'wp-user-manager' ) ) ); ?></h4>

<?php

foreach ( $data->products as $product ) :
	if ( ! empty( $data->allowed_prices ) && empty( array_intersect( array_keys( $product['prices'] ), $data->allowed_prices ) ) ) {
		continue;
	}
	?>

	<div class="wpum-row wpum-form" style="margin-bottom: 1rem;">
		<div class="wpum-col-xs-3">
			<?php echo esc_html( $product['name'] ); ?>
		</div>
		<div class="wpum-col-xs-3">
			<?php
			foreach ( $product['prices'] as $price_id => $price ) :
				if ( ! empty( $allowed_prices ) && ! in_array( $price_id, $data->allowed_prices, true ) ) {
					continue;
				}
				?>
				<strong><?php echo esc_html( \WPUserManager\Stripe\Stripe::currencySymbol( $price['currency'] ) . number_format( $price['unit_amount'] / 100 ) ); ?></strong><?php echo esc_html( isset( $price['recurring']['interval'] ) ? '/' . $price['recurring']['interval'] : '' ); ?>
				<br>
			<?php endforeach; ?>
		</div>
		<div class="wpum-col-xs-3">
			<?php
			foreach ( $product['prices'] as $price_id => $price ) :
				if ( ! empty( $data->allowed_prices ) && ! in_array( $price_id, $data->allowed_prices, true ) ) {
					continue;
				}
				?>
				<button class="wpum-stripe-checkout button"
						data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpum-stripe-plan-' . $price_id ) ); ?>"
						data-plan-id="<?php echo esc_attr( $price_id ); ?>">
					<?php echo esc_html( apply_filters( 'wpum_stripe_account_billing_plan_button_label', __( 'Select Plan', 'wp-user-manager' ) ) ) . '</h4>'; ?>
				</button><br>
			<?php endforeach; ?>
		</div>
	</div>
<?php endforeach; ?>
