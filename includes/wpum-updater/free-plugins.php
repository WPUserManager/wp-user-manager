<?php
/**
 * Deliver upgrades for plugins that are only available as part of addon bundles
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'admin_init', 'wpum_free_plugins_auto_updater', 0 );

function wpum_free_plugins_auto_updater() {
	$api_url = 'https://wpusermanager.com';

	if ( defined( 'WPUMDA_PLUGIN_FILE' ) ) {
		new WPUM_EDD_SL_Plugin_Updater( $api_url, WPUMDA_PLUGIN_FILE, array(
			'version'   => WPUMDA_VERSION,
			'item_id'   => 25531,
			'license'   => '72fe94f839964c6210607d11025ab599',
			'item_name' => 'Delete Account',
			'author'    => 'WP User Manager',
			'url'       => home_url(),
		) );
	}

	if ( defined( 'WPUMPD_PLUGIN_FILE' ) ) {
		new WPUM_EDD_SL_Plugin_Updater( $api_url, WPUMPD_PLUGIN_FILE, array(
			'version'   => WPUMPD_VERSION,
			'item_id'   => 25551,
			'license'   => '72fe94f839964c6210607d11025ab599',
			'item_name' => 'Personal Data',
			'author'    => 'WP User Manager',
			'url'       => home_url(),
		) );
	}

}
