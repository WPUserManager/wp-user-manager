<div
	class="wpum-stripe-connect-account-info vue-wp-notice notice-info inline"
	data-account-id="<?php echo esc_attr( $stripe_connect_account_id ); ?>"
	data-nonce="<?php echo wp_create_nonce( 'wpum-stripe-connect-account-information' ); ?>"
	data-gateway-mode="<?php echo esc_attr( $mode ); ?>">
	<p>
		<em><?php esc_html_e( 'Retrieving account information', 'wp-user-manager' ); ?>&hellip;</em>
	</p>
</div>
