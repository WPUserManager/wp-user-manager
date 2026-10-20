<?php
/**
 * E2E helpers for the Stripe Connect spec. Copied into mu-plugins while it runs.
 */

// Never call the real Connect server: the credentials request fails fast instead.
add_filter(
	'wpum_stripe_connect_base_url',
	function () {
		return 'http://127.0.0.1:9';
	}
);

/**
 * The test-mode Connect button URL, as the settings page builds it for the current user.
 *
 * @return string
 */
function wpum_e2e_connect_url() {
	return ( new \WPUserManager\Stripe\Connect() )->connect_url( true );
}

/**
 * Build the Connect buttons again, as every settings load and REST request does.
 *
 * @param int $times
 */
function wpum_e2e_rebuild_connect_buttons( $times ) {
	$connect = new \WPUserManager\Stripe\Connect();
	for ( $i = 0; $i < (int) $times; $i++ ) {
		$connect->connect_url( true );
		$connect->connect_url( false );
	}
}
