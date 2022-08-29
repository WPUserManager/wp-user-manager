<?php
/**
 * Handles display of admin notices for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM Admin Notices class that registers all the notices that need to be displayed.
 */
class WPUM_Admin_Notices {

	/**
	 * Get things started.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_notices' ) );
		add_action( 'admin_init', array( $this, 'fix_data_installation' ) );
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
			$url_rate       = 'http://wordpress.org/support/view/plugin-reviews/wp-user-manager?filter=5#new-post';
			$current_user   = wp_get_current_user();
			$current_user   = $current_user->display_name;
			$rating_message = sprintf(
				__( "Hey %1\$s, looks like you've been using the %2\$s plugin for some time now - that's awesome! <br/> Could you please give it a review on wordpress.org? Just to help us spread the word and boost our motivation :) <br/> <br/><a href='%3\$s' class='button button-primary' target='_blank'>Yes, you deserve it!</a>", 'wp-user-manager' ),
				$current_user,
				'<b>WP User Manager</b>',
				$url_rate
			);
			WPUM()->notices->register_notice( 'wpum_rating', 'success', $rating_message );
		}

		if ( $this->field_groups_are_empty() && $this->fields_are_empty() && $this->registration_forms_are_empty() ) {

			$url = add_query_arg( array( 'wpum_fix_installation_data' => true ), admin_url() );

			$btn           = '<a href="' . esc_url( $url ) . '" class="button-primary">' . esc_html__( 'Fix data installation', 'wp-user-manager' ) . '</a>';
			$error_message = esc_html__( 'It looks like WP User Manager failed to install it\'s default data. To fix the issue please click the button below.', 'wp-user-manager' ) . '</br><br/>' . $btn;

			WPUM()->notices->register_notice( 'wpum_fix_installation', 'error', $error_message );

		}

		if ( empty( get_option( 'permalink_structure' ) ) ) {
			$page       = 'options-permalink.php';
			$update_url = admin_url() . $page;
			$message    = __( '<strong>WP User Manager</strong> requires your site to have a \'pretty\' permalink structure enabled instead of the default \'Plain\'.', 'wp-user-manager' );
			global $pagenow;
			if ( isset( $pagenow ) && $page !== $pagenow ) {
				$message .= ' <a href="' . $update_url . '">' . esc_html__( 'Change permalinks', 'wp-user-manager' ) . '</a>';
			}
			WPUM()->notices->register_notice( 'wpum_permalinks', 'warning', $message, array( 'dismissible' => false ) );
		}

	}

	/**
	 * Verify groups are empty.
	 *
	 * @return boolean
	 */
	private function field_groups_are_empty() {

		$fields_groups = WPUM()->fields_groups->get_groups();

		if ( empty( $fields_groups ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Verify fields are empty.
	 *
	 * @return boolean
	 */
	private function fields_are_empty() {

		global $wpdb;

		$empty = false;

		if ( $this->field_groups_are_empty() && empty( WPUM()->fields->get_fields() ) ) {
			return true;
		}

		return $empty;

	}

	/**
	 * Verify there are no registration forms.
	 *
	 * @return boolean
	 */
	private function registration_forms_are_empty() {

		$forms = WPUM()->registration_forms->get_forms();

		if ( empty( $forms ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Fix data installation if something went wrong.
	 *
	 * @return void
	 */
	public function fix_data_installation() {

		if ( current_user_can( 'manage_options' ) && isset( $_GET['wpum_fix_installation_data'] ) ) { // phpcs:ignore
			delete_option( 'wpum_setup_is_complete' );
			delete_option( 'wpum_version_upgraded_from' );
			delete_option( 'wpum_version_upgraded_from' );

			wpum_install_default_field_group();
			$fields = wpum_install_fields();
			wpum_install_cover_image_field();
			wpum_setup_default_custom_search_fields();
			wpum_install_registration_form( $fields );

			update_option( 'wpum_setup_is_complete', true );

			$current_version = get_option( 'wpum_version' );
			if ( $current_version ) {
				update_option( 'wpum_version_upgraded_from', $current_version );
			}

			update_option( 'wpum_data_installation_fix_check', true );

			// translators: %s WPUM admin URL
			$message = sprintf( __( 'WP User Manager has installed default data. You can go back to your <a href="%s">admin panel.</a>', 'wp-user-manager' ), admin_url() );

			wp_die( wp_kses_post( $message ) );
		}
	}

}

new WPUM_Admin_Notices();
