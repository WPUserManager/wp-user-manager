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

		// Display a notice asking the user to leave a rating after 14 days.
		$install_date = get_option( 'wpum_activation_date' );
		$past_date    = strtotime( '-14 days' );

		if ( $install_date && $past_date >= $install_date ) {
			$url_rate     = 'http://wordpress.org/support/view/plugin-reviews/wp-user-manager?filter=5#postform';
			$current_user = wp_get_current_user();
			$current_user = $current_user->display_name;
			$rating_message = sprintf(
				__( "Hey %s, looks like you've been using the %s plugin for some time now - that's awesome! <br/> Could you please give it a review on wordpress.org? Just to help us spread the word and boost our motivation :) <br/> <br/><a href='%s' class='button button-primary' target='_blank'>Yes, you deserve it!</a>", 'wpum' ),
				$current_user,
				'<b>WP User Manager</b>',
				$url_rate
			);
			WPUM()->notices->register_notice( 'wpum_rating', 'success', $rating_message );
		}

	}

}

new WPUM_Admin_Notices;
