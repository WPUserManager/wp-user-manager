<?php
/**
 * Handles display of admin notices for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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

		// Display a notice asking the user to leave a rating after 14 days.
		$install_date = get_option( 'wpum_activation_date' );
		$past_date    = strtotime( '-14 days' );

		if ( $install_date && $past_date >= $install_date ) {
			$url_rate     = 'http://wordpress.org/support/view/plugin-reviews/wp-user-manager?filter=5#postform';
			$current_user = wp_get_current_user();
			$current_user = $current_user->display_name;
			$rating_message = sprintf(
				__( "Hey %s, looks like you've been using the %s plugin for some time now - that's awesome! <br/> Could you please give it a review on wordpress.org? Just to help us spread the word and boost our motivation :) <br/> <br/><a href='%s' class='button button-primary' target='_blank'>Yes, you deserve it!</a>", 'wp-user-manager' ),
				$current_user,
				'<b>WP User Manager</b>',
				$url_rate
			);
			WPUM()->notices->register_notice( 'wpum_rating', 'success', $rating_message );
		}

	}

}

new WPUM_Admin_Notices;
