<?php
/**
 * Uninstall WPUM.
 *
 * @copyright   Copyright (c) 2015, Alessandro Tesoro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) exit;

// Load WPUM file.
include_once( 'wp-user-manager.php' );

global $wpdb;

// Delete post type contents
$wpum_post_types = array( 'wpum_directory' );

foreach ( $wpum_post_types as $post_type ) {
	$items = get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
	if ( $items ) {
		foreach ( $items as $item ) {
			wp_delete_post( $item, true );
		}
	}
}

// Delete options from the database.
$options_to_delete = [
	'wpdb_wpum_fieldmeta_version',
	'wpdb_wpum_fields_version',
	'wpdb_wpum_fieldsgroups_version',
	'wpdb_wpum_registration_formmeta_version',
	'wpdb_wpum_registration_forms_meta_version',
	'wpdb_wpum_registration_forms_version',
	'wpdb_wpum_search_fields_version',
	'wpum_activation_date',
	'wpum_email',
	'wpum_permalink',
	'wpum_settings',
	'wpum_version',
	'wpum_version_upgraded_from',
	'wpum_completed_upgrades'
];

foreach( $options_to_delete as $option ) {
	delete_option( $option );
}

// Delete tables created by the plugin.
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wpum_fieldmeta" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wpum_fields" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wpum_fieldsgroups" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wpum_registration_formmeta" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wpum_registration_forms" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "wpum_search_fields" );
