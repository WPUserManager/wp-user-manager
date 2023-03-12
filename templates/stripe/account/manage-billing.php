<?php
/**
 * The Template for the Account page manage billing section
 *
 * This template can be overridden by copying it to yourtheme/wpum/stripe/account/manage-billing.php
 *
 * @version 2.9.0
 */

?>

<div class="wpum-form">
	<p> <?php /* translators: %s the plan name. */ echo esc_html( sprintf( __( 'You\'re currently on the %s plan.', 'wp-user-manager' ), $data->plan->name ) ); ?></p>
	<button id="wpum-stripe-manage-billing" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpum-stripe-manage-billing' ) ); ?>" class="button" style="margin-top: 1rem"><?php esc_html_e( 'Manage Billing', 'wp-user-manager' ); ?></button>
</div>
