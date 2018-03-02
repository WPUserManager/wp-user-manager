<?php
/**
 * Handles display of admin notices.
 */

/**
 * WPUM Admin Notices class that registers all the notices that need to be displayed.
 */
class WPUM_Admin_Notices {

	/**
	 * Get things started.
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'register_notices' ] );
	}

	/**
	 * Register all notices.
	 *
	 * @return void
	 */
	public function register_notices() {

		// Display plugin activation notice.
		if( get_transient( 'wpum-activation-notice' ) ) {
			$activation_message  = '<strong>' . sprintf( __( 'Welcome to WP User Manager %s', 'wpum' ), WPUM_VERSION ) . '</strong>';
			$activation_message .= '<br/>';
			$activation_message .= __( 'Thank you for installing the latest version! WP User Manager is ready to provide improved control over your WordPress powered community.', 'wpum' );
			$activation_message .= '<br/>';
			$activation_message .= __( 'WPUM has automatically installed it\'s required data and pages and it\'s now ready to be used.' );
			$activation_message .= '<br/><br/>';
			$activation_message .= '<a href="https://docs.wpusermanager.com/" target="_blank" class="button">' . __( 'Read documentation' ) . '</a>';
			WPUM()->notices->register_notice( 'wpum_activated', 'success', $activation_message );
		}

	}

}

new WPUM_Admin_Notices;
