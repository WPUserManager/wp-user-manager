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

	$wpum_updates->register(
		array(
			'id'       => 'v2_migration_cover_field',
			'version'  => '2.0.0',
			'callback' => 'wpum_v200_upgrade_cover_field_callback',
		)
	);

	$wpum_updates->register(
		array(
			'id'       => 'v2_migration_install_registration_form',
			'version'  => '2.0.0',
			'callback' => 'wpum_v200_upgrade_install_registration_form_callback',
		)
	);

	$wpum_updates->register(
		array(
			'id'       => 'v2_migration_emails',
			'version'  => '2.0.0',
			'callback' => 'wpum_v200_upgrade_emails_callback',
		)
	);

	$wpum_updates->register(
		array(
			'id'       => 'v2_install_search_fields',
			'version'  => '2.0.0',
			'callback' => 'wpum_v200_upgrade_install_search_fields_callback',
		)
	);

	$wpum_updates->register(
		array(
			'id'       => 'v2_migrate_directories',
			'version'  => '2.0.0',
			'callback' => 'wpum_v200_migrate_directories_callback',
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

/**
 * Migration callback to move page options to the new array format.
 *
 * @return void
 */
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

	// Migrate frontend redirects options.
	$after_login        = wpum_get_option( 'login_redirect' );
	$after_logout       = wpum_get_option( 'logout_redirect' );
	$after_registration = wpum_get_option( 'registration_redirect' );

	if( ! is_array( $after_login ) && ! empty( $after_login ) ) {
		$after_login = [ $after_login ];
		wpum_update_option( 'login_redirect', $after_login );
	}

	if( ! is_array( $after_logout ) && ! empty( $after_logout ) ) {
		$after_logout = [ $after_logout ];
		wpum_update_option( 'logout_redirect', $after_logout );
	}

	if( ! is_array( $after_registration ) && ! empty( $after_registration ) ) {
		$after_registration = [ $after_registration ];
		wpum_update_option( 'registration_redirect', $after_registration );
	}

	// Migrate backend redirects options.
	$wp_login_signup_redirect   = wpum_get_option( 'wp_login_signup_redirect' );
	$wp_login_password_redirect = wpum_get_option( 'wp_login_password_redirect' );
	$backend_profile_redirect   = wpum_get_option( 'backend_profile_redirect' );

	if( ! is_array( $wp_login_signup_redirect ) && ! empty( $wp_login_signup_redirect ) ) {
		$wp_login_signup_redirect = [ $wp_login_signup_redirect ];
		wpum_update_option( 'wp_login_signup_redirect', $wp_login_signup_redirect );
	}

	if( ! is_array( $wp_login_password_redirect ) && ! empty( $wp_login_password_redirect ) ) {
		$wp_login_password_redirect = [ $wp_login_password_redirect ];
		wpum_update_option( 'wp_login_password_redirect', $wp_login_password_redirect );
	}

	if( ! is_array( $backend_profile_redirect ) && ! empty( $backend_profile_redirect ) ) {
		$backend_profile_redirect = [ $backend_profile_redirect ];
		wpum_update_option( 'backend_profile_redirect', $backend_profile_redirect );
	}

	$wpum_updates->set_percentage( 100, 100 );

	wpum_set_upgrade_complete( 'v2_migration_options' );

}

/**
 * Create the new cover field introduced with WPUM 2.0
 *
 * @return void
 */
function wpum_v200_upgrade_cover_field_callback() {

	$wpum_updates = WPUM_Updates::get_instance();

	wpum_install_cover_image_field();

	$wpum_updates->set_percentage( 100, 100 );

	wpum_set_upgrade_complete( 'v2_migration_cover_field' );

}

/**
 * Install the registration form within the database during migration.
 *
 * @return void
 */
function wpum_v200_upgrade_install_registration_form_callback() {

	$wpum_updates = WPUM_Updates::get_instance();

	wpum_install_registration_form();

	$wpum_updates->set_percentage( 100, 100 );

	wpum_set_upgrade_complete( 'v2_migration_install_registration_form' );

}

/**
 * Migrate currently installed emails to the new format.
 *
 * @return void
 */
function wpum_v200_upgrade_emails_callback() {

	$wpum_updates    = WPUM_Updates::get_instance();
	$existing_emails = get_option( 'wpum_emails' );
	$new_emails      = '';

	if( ! empty( $existing_emails ) && is_array( $existing_emails ) ) {

		// Grab the existing registration email and reformat it for the new emails.
		if( array_key_exists( 'register', $existing_emails ) ) {

			$existing_registration_email = $existing_emails[ 'register' ];
			$existing_email_subject      = $existing_registration_email[ 'subject' ];
			$existing_email_message      = $existing_registration_email[ 'message' ];

			$new_emails[ 'registration_confirmation' ] = [
				'title'   => $existing_email_subject,
				'subject' => $existing_email_subject,
				'content' => $existing_email_message,
				'footer'  => '<a href="{siteurl}">{sitename}</a>'
			];

		}

		if( array_key_exists( 'password', $existing_emails ) ) {

			$existing_password_recovery_email = $existing_emails[ 'password' ];
			$existing_email_subject           = $existing_password_recovery_email[ 'subject' ];
			$existing_email_message           = $existing_password_recovery_email[ 'message' ];

			$new_emails[ 'password_recovery_request' ] = [
				'title'   => $existing_email_subject,
				'subject' => $existing_email_subject,
				'content' => $existing_email_message,
				'footer'  => '<a href="{siteurl}">{sitename}</a>'
			];

		}

	}

	if( is_array( $new_emails ) && ! empty( $new_emails ) ) {
		update_option( 'wpum_email', $new_emails );
		$wpum_updates->set_percentage( 100, 100 );
	}

	wpum_set_upgrade_complete( 'v2_migration_emails' );

}

/**
 * Install search fields for version 2.0.0 during migration.
 *
 * @return void
 */
function wpum_v200_upgrade_install_search_fields_callback() {

	$wpum_updates = WPUM_Updates::get_instance();

	wpum_setup_default_custom_search_fields();

	$wpum_updates->set_percentage( 100, 100 );

	wpum_set_upgrade_complete( 'v2_install_search_fields' );

}

/**
 * Migrate all available directories to the new fields format.
 *
 * @return void
 */
function wpum_v200_migrate_directories_callback() {

	$wpum_updates = WPUM_Updates::get_instance();

	$directories = new WP_Query( array(
			'paged'          => $wpum_updates->step,
			'status'         => 'any',
			'order'          => 'ASC',
			'post_type'      => 'wpum_directory',
			'posts_per_page' => 20,
		)
	);

	if ( $directories->have_posts() ) {

		$wpum_updates->set_percentage( $directories->found_posts, ( $wpum_updates->step * 20 ) );

		while ( $directories->have_posts() ) {

			$directories->the_post();

			$directory_id = get_the_ID();

			// Get all the existing custom fields.
			$directory_roles        = get_post_meta( $directory_id, 'directory_roles', true );
			$display_search_form    = get_post_meta( $directory_id, 'display_search_form', true );
			$excluded_ids           = get_post_meta( $directory_id, 'excluded_ids', true );
			$profiles_per_page      = get_post_meta( $directory_id, 'profiles_per_page', true );
			$directory_template     = get_post_meta( $directory_id, 'directory_template', true );
			$display_sorter         = get_post_meta( $directory_id, 'display_sorter', true );
			$display_amount         = get_post_meta( $directory_id, 'display_amount', true );
			$default_sorting_method = get_post_meta( $directory_id, 'default_sorting_method', true );

			carbon_set_post_meta( $directory_id, 'directory_assigned_roles', $directory_roles );

			if( $display_search_form ) {
				carbon_set_post_meta( $directory_id, 'directory_search_form', 'yes' );
			}

			carbon_set_post_meta( $directory_id, 'directory_excluded_users', $excluded_ids );
			carbon_set_post_meta( $directory_id, 'directory_profiles_per_page', $profiles_per_page );

			if( $display_sorter ) {
				carbon_set_post_meta( $directory_id, 'directory_display_sorter', 'yes' );
			}
			if( $display_amount ) {
				carbon_set_post_meta( $directory_id, 'directory_display_amount_filter', 'yes' );
			}

			carbon_set_post_meta( $directory_id, 'directory_sorting_method', $default_sorting_method );

		}

		wp_reset_postdata();

	} else {
		wpum_set_upgrade_complete( 'v2_migrate_directories' );
	}

}
