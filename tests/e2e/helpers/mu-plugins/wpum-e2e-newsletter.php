<?php
/**
 * E2E helpers for the newsletter opt-in spec. Copied into mu-plugins while it runs.
 */

// Never call the real list: answer as Sendy does on success, and keep the request for the spec.
add_filter(
	'pre_http_request',
	function ( $preempt, $args, $url ) {
		if ( 0 !== strpos( $url, 'https://s.wpusermanager.com/' ) ) {
			return $preempt;
		}

		update_option( 'wpum_e2e_newsletter_request', $args['body'], false );

		return array(
			'headers'  => array( 'location' => 'https://wpusermanager.com/subscribed/' ),
			'body'     => '',
			'response' => array(
				'code'    => 302,
				'message' => 'Found',
			),
			'cookies'  => array(),
		);
	},
	10,
	3
);
