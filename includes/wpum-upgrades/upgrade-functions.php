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

	/*
	$wpum_updates->register(
		array(
			'id'       => 'upgrade_to_v2_test28',
			'version'  => '2.0.0',
			'callback' => 'wpum_upgrade_to_v2_test',
		)
	);*/

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