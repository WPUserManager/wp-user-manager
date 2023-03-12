<?php
/**
 * The Template for the Stripe admin connection
 *
 * This template can be overridden by copying it to yourtheme/wpum/stripe/admin-disconnect.php
 *
 * @version 2.9.0
 */

?>
<div
	class="wpum-stripe-connect-account-info vue-wp-notice notice-info inline"
	data-account-id="<?php echo esc_attr( $data->stripe_connect_account_id ); ?>"
	data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpum-stripe-connect-account-information' ) ); ?>"
	data-gateway-mode="<?php echo esc_attr( $data->mode ); ?>">
	<p>
		<em><?php esc_html_e( 'Retrieving account information', 'wp-user-manager' ); ?>&hellip;</em>
	</p>
</div>
