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

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Runs on plugin install by setting up the post types, custom taxonomies, flushing rewrite rules to initiate the new
 * slugs and also creates the plugin and populates the settings fields for those plugin pages.
 *
 * @param boolean $network_wide
 * @return void
 */
function wp_user_manager_install( $network_wide = false ) {

	global $wpdb;

	if ( is_multisite() && $network_wide ) {
		foreach ( $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs LIMIT 100" ) as $blog_id ) {
			switch_to_blog( $blog_id );
			wpum_run_install();
			restore_current_blog();
		}
	} else {
		wpum_run_install();
	}

}

/**
 * Run the installation process of the plugin.
 *
 * @return void
 */
function wpum_run_install() {

	// Setup a transient to display the activation notice.
    set_transient( 'wpum-activation-notice', true, 5 );

}
