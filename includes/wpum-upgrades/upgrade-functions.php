<?php
/**
 * Register all functionalities related to upgrades.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 *
 * * NOTICE: When adding new upgrade notices, please be sure to put the action into the upgrades array during install:
 * /includes/install.php
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Perform automatic database upgrades when necessary.
 *
 * @return void
 */
function wpum_do_automatic_upgrades() {

	$did_upgrade  = false;
	$wpum_version = preg_replace( '/[^0-9.].*/', '', get_option( 'wpum_version' ) );

	if ( ! $wpum_version ) {
		$wpum_version = '2.0.0';
	}

	switch ( true ) {

	}
	if ( $did_upgrade ) {
		update_option( 'wpum_version', preg_replace( '/[^0-9.].*/', '', WPUM_VERSION ) );
	}
}
add_action( 'admin_init', 'wpum_do_automatic_upgrades' );
add_action( 'wpum_upgrades', 'wpum_do_automatic_upgrades' );

/**
 * Display Upgrade Notices.
 * IMPORTANT: ALSO UPDATE INSTALL.PHP WITH THE ID OF THE UPGRADE ROUTINE SO IT DOES NOT AFFECT NEW INSTALLS.
 *
 * @param WPUM_Updates $wpum_updates
 * @return void
 */
function wpum_show_upgrade_notices( $wpum_updates ) {

	$wpum_updates->register(
		array(
			'id'       => 'v2_migration_options',
			'version'  => '2.0.0',
			'callback' => 'wpum_v200_upgrade_options_callback',
		)
	);

}
add_action( 'wpum_register_updates', 'wpum_show_upgrade_notices' );

/**
 * Triggers all upgrade functions
 * This function is usually triggered via AJAX
 *
 * @return void
 */
function wpum_trigger_upgrades() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do WPUM upgrades.' ), esc_html__( 'Error' ), array(
			'response' => 403,
		) );
	}
	$wpum_version = get_option( 'wpum_version' );
	if ( ! $wpum_version ) {
		// 2.0.0 is the first version to use this option so we must add it.
		$wpum_version = '2.0.0';
		add_option( 'wpum_version', $wpum_version );
	}
	update_option( 'wpum_version', WPUM_VERSION );
	delete_option( 'wpum_doing_upgrade' );
	if ( DOING_AJAX ) {
		die( 'complete' );
	} // End if().
}
add_action( 'wp_ajax_wpum_trigger_upgrades', 'wpum_trigger_upgrades' );

function wpum_v200_upgrade_options_callback() {

	$wpum_updates = WPUM_Updates::get_instance();

	// Get existing page options.
	$login_page             = wpum_get_option( 'login_page' );
	$password_recovery_page = wpum_get_option( 'password_recovery_page' );
	$registration_page      = wpum_get_option( 'registration_page' );
	$account_page           = wpum_get_option( 'account_page' );
	$profile_page           = wpum_get_option( 'profile_page' );
	$terms_page             = wpum_get_option( 'terms_page' );

	// Create an array for each of the page options.
	if( ! is_array( $login_page ) && ! is_array( $password_recovery_page ) && ! is_array( $registration_page ) && ! is_array( $account_page ) && ! is_array( $profile_page ) && ! is_array( $terms_page ) ) {

		$login_page             = [ $login_page ];
		$password_recovery_page = [ $password_recovery_page ];
		$registration_page      = [ $registration_page ];
		$account_page           = [ $account_page ];
		$profile_page           = [ $profile_page ];
		$terms_page             = [ $terms_page ];

		// Now update the page options into the db with the newly generated array.
		wpum_update_option( 'login_page', $login_page );
		wpum_update_option( 'password_recovery_page', $password_recovery_page );
		wpum_update_option( 'registration_page', $registration_page );
		wpum_update_option( 'account_page', $account_page );
		wpum_update_option( 'profile_page', $profile_page );
		wpum_update_option( 'terms_page', $terms_page );

	}

	wpum_set_upgrade_complete( 'v2_migration_options' );

}

print_r( wpum_get_option( 'adminbar_roles' ) );
