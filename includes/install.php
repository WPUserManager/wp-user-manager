<?php
/**
 * Install function.
 *
 * @package     wp-user-manager
 * @subpackage  Functions/Install
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs on plugin install by setting up the post types, custom taxonomies, flushing rewrite rules to initiate the new
 * slugs and also creates the plugin and populates the settings fields for those plugin pages.
 *
 * @param boolean $network_wide
 *
 * @return void
 */
function wp_user_manager_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {
		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) { // phpcs:ignore
			switch_to_blog( $blog_id );
			wpum_run_install();
			restore_current_blog();
		}
	} else {
		wpum_run_install();
	}

}

/**
 * Generates core pages and updates settings panel with the newly created pages.
 *
 * @return void
 * @since 1.0.0
 */
function wpum_generate_pages() {
	$is_block_editor = function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( 'page' );
	// Generate login page

	if ( ! wpum_get_option( 'login_page' ) || false === get_post_status( wpum_get_option( 'login_page' )[0] ) ) {
		$login_content = $is_block_editor ? '<!-- wp:wpum/login-form /-->' : '[wpum_login_form psw_link="yes" register_link="yes"]';

		$login = wp_insert_post(
			array(
				'post_title'     => __( 'Log In', 'wp-user-manager' ),
				'post_content'   => $login_content,
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);
		wpum_update_option( 'login_page', array( $login ) );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'password_recovery_page' ) || false === get_post_status( wpum_get_option( 'password_recovery_page' )[0] ) ) {
		$psw_content = $is_block_editor ? '<!-- wp:wpum/password-recovery-form /-->' : '[wpum_password_recovery login_link="yes" register_link="yes"]';

		$psw = wp_insert_post(
			array(
				'post_title'     => __( 'Password Reset', 'wp-user-manager' ),
				'post_content'   => $psw_content,
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);
		wpum_update_option( 'password_recovery_page', array( $psw ) );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'registration_page' ) || false === get_post_status( wpum_get_option( 'registration_page' )[0] ) ) {
		$reg_content = $is_block_editor ? '<!-- wp:wpum/registration-form /-->' : '[wpum_register login_link="yes" psw_link="yes"]';

		$register = wp_insert_post(
			array(
				'post_title'     => __( 'Register', 'wp-user-manager' ),
				'post_content'   => $reg_content,
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);
		wpum_update_option( 'registration_page', array( $register ) );
	}
	// Generate account page
	if ( ! wpum_get_option( 'account_page' ) || false === get_post_status( wpum_get_option( 'account_page' )[0] ) ) {
		$account_content = $is_block_editor ? '<!-- wp:wpum/account-page /-->' : '[wpum_account]';

		$account = wp_insert_post(
			array(
				'post_title'     => __( 'Account', 'wp-user-manager' ),
				'post_content'   => $account_content,
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);
		wpum_update_option( 'account_page', array( $account ) );
	}
	// Generate password recovery page
	if ( ! wpum_get_option( 'profile_page' ) || false === get_post_status( wpum_get_option( 'profile_page' )[0] ) ) {
		$profile_content = $is_block_editor ? '<!-- wp:wpum/profile-page /-->' : '[wpum_profile]';

		$profile = wp_insert_post(
			array(
				'post_title'     => __( 'Profile', 'wp-user-manager' ),
				'post_content'   => $profile_content,
				'post_status'    => 'publish',
				'post_author'    => 1,
				'post_type'      => 'page',
				'comment_status' => 'closed',
			)
		);
		wpum_update_option( 'profile_page', array( $profile ) );
	}
}

/**
 * Install the registration form into the database.
 *
 * @param array $fields
 *
 * @return void
 */
function wpum_install_registration_form( $fields = array() ) {

	$default_form_id = WPUM()->registration_forms->insert(
		array(
			'name' => esc_html__( 'Default registration form', 'wp-user-manager' ),
		)
	);

	$default_form = new WPUM_Registration_Form( $default_form_id );
	$default_form->add_meta( 'default', true );
	$default_form->add_meta( 'role', get_option( 'default_role' ) );

	$default_fields = array();

	foreach ( $fields as $field ) {
		if ( in_array( $field->get_type(), array( 'user_email', 'user_password' ), true ) ) {
			$default_fields[] = $field->get_ID();
		}
	}

	$default_form->add_meta( 'fields', $default_fields );

}

/**
 * Install emails into the database.
 *
 * @return array
 */
function wpum_install_emails() {
	$emails = array(
		'registration_confirmation'       => array(
			'title'   => 'Welcome to {sitename}',
			'footer'  => '<a href="{siteurl}">{sitename}</a>',
			'content' => '<p>Hello {username}, and welcome to {sitename}. We’re thrilled to have you on board. </p>
<p>For reference, here\'s your login information:</p>
<p>Username: {username}<br />Login page: {login_page_url}<br />Password: {password}</p>
<p>Thanks,<br />{sitename}</p>',
			'subject' => 'Welcome to {sitename}',
		),
		'registration_admin_notification' => array(
			'title'   => 'New User Registration',
			'content' => '<p>New user registration on your site {sitename}:<br></p>
<p>Username: {username}</p>
<p>E-mail: {email}</p>',
			'subject' => '[{sitename}] New User Registration',
		),
		'password_recovery_request'       => array(
			'subject' => 'Reset your {sitename} password',
			'title'   => 'Reset your {sitename} password',
			'content' => '<p>Hello {username},</p>
<p>You are receiving this message because you or somebody else has attempted to reset your password on {sitename}.</p>
<p>If this was a mistake, just ignore this email and nothing will happen.</p>
<p>To reset your password, visit the following address:</p>
<p>{recovery_url}</p>',
			'footer'  => '<a href="{siteurl}">{sitename}</a>',
		),
	);

	$emails = array_merge( $emails, get_option( 'wpum_email', array() ) );

	update_option( 'wpum_email', $emails );

	return $emails;
}

/**
 * Run the installation process of the plugin.
 *
 * @return void
 */
function wpum_run_install() {

	// Enable registrations on the site.
	update_option( 'users_can_register', true );

	// Store plugin installation date.
	add_option( 'wpum_activation_date', strtotime( 'now' ) );

	// Add Upgraded From Option.
	$current_version = get_option( 'wpum_version' );
	if ( $current_version ) {
		update_option( 'wpum_version_upgraded_from', $current_version );
	}

	// Install default pages
	wpum_generate_pages();

	// Add some default options.
	wpum_update_option( 'login_method', 'username_email' );
	wpum_update_option( 'email_template', 'default' );
	wpum_update_option( 'from_email', get_option( 'admin_email' ) );
	wpum_update_option( 'from_name', get_option( 'blogname' ) );
	wpum_update_option( 'guests_can_view_profiles', true );
	wpum_update_option( 'members_can_view_profiles', true );
	wpum_update_option( 'roles_editor', true );

	// Clear the permalinks.
	flush_rewrite_rules();

	// Setup permalinks for WPUM.
	update_option( 'wpum_permalink', 'username' );

	if ( ! $current_version ) {
		update_option( 'v202_upgrade', true );
	}

	// Check if all tables are there.
	$tables = array(
		'fields'                => new WPUM_DB_Table_Fields(),
		'fieldmeta'             => new WPUM_DB_Table_Field_Meta(),
		'fieldsgroups'          => new WPUM_DB_Table_Fields_Groups(),
		'registrationforms'     => new WPUM_DB_Table_Registration_Forms(),
		'registrationformsmeta' => new WPUM_DB_Table_Registration_Forms_Meta(),
		'searchfields'          => new WPUM_DB_Table_Search_Fields(),
		'subscriptions'         => new WPUM_DB_Table_Stripe_Subscriptions(),
		'invoice'               => new WPUM_DB_Table_Stripe_Invoices(),
	);

	foreach ( $tables as $key => $table ) {
		if ( ! $table->exists() ) {
			$table->create();
		}
	}

	wpum_install_emails();

	// Update current version.
	update_option( 'wpum_version', WPUM_VERSION );

	// Add the transient to redirect.
	set_transient( '_wpum_activation_redirect', true, 30 );

}

/**
 * Install default data when new site is added.
 *
 * @param object $site
 *
 * @return void
 */
function wpum_multisite_new_site( $site ) {
	if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}
	if ( is_plugin_active_for_network( 'wp-user-manager/wp-user-manager.php' ) ) {
		switch_to_blog( $site->blog_id );
		wpum_run_install();
		restore_current_blog();
	}
}

add_action( 'wp_initialize_site', 'wpum_multisite_new_site' );
