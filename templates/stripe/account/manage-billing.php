<?php
/*
 * The Template for the Account page manage billing section
 *
 * This template can be overridden by copying it to yourtheme/wpum/stripe/account/manage-billing.php
 *
 * @version 2.9.0
 */
?>

<div class="wpum-form">
	<p> <?php echo sprintf( __( 'You\'re currently on the %s plan.', 'wp-user-manager' ), $data->plan->name ); ?></p>
	<button id="wpum-stripe-manage-billing" class="button" style="margin-top: 1rem"><?php _e( 'Manage Billing', 'wp-user-manager' ); ?></button>
</div>
