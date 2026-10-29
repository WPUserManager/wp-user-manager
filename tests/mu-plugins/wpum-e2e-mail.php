<?php
/**
 * E2E only: pretend emails are sent, and record them, while a test asks for it.
 *
 * The wp-env containers have no mail server, so wp_mail() always fails there.
 * A spec turns capture on with `wp option update wpum_e2e_capture_mail 1`,
 * reads what was sent from the `wpum_e2e_sent_mail` option, and turns it off
 * again afterwards. With the option off, nothing changes.
 */

add_filter(
	'pre_wp_mail',
	function ( $return, $atts ) {
		if ( ! get_option( 'wpum_e2e_capture_mail' ) ) {
			return $return;
		}

		$sent   = (array) get_option( 'wpum_e2e_sent_mail', array() );
		$sent[] = array(
			'to'      => $atts['to'],
			'subject' => $atts['subject'],
		);
		update_option( 'wpum_e2e_sent_mail', $sent, false );

		return true;
	},
	10,
	2
);
