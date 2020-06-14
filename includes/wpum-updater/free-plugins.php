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

	$delete_account = WP_PLUGIN_DIR . '/wpum-delete-account/wpum-delete-account.php';
	if ( defined( 'WPUMDA_PLUGIN_FILE' ) || file_exists( $delete_account ) ) {
		new WPUM_EDD_SL_Plugin_Updater( $api_url, $delete_account, array(
			'version'   => WPUMDA_VERSION,
			'item_id'   => 25531,
			'license'   => '72fe94f839964c6210607d11025ab599',
			'item_name' => 'Delete Account',
			'author'    => 'WP User Manager',
			'url'       => home_url(),
		) );
	}

	$personal_data = WP_PLUGIN_DIR . '/wpum-personal-data/wpum-personal-data.php';
	if ( defined( 'WPUMPD_PLUGIN_FILE' ) || file_exists( $personal_data ) ) {
		new WPUM_EDD_SL_Plugin_Updater( $api_url, $personal_data, array(
			'version'   => WPUMPD_VERSION,
			'item_id'   => 25551,
			'license'   => '72fe94f839964c6210607d11025ab599',
			'item_name' => 'Personal Data',
			'author'    => 'WP User Manager',
			'url'       => home_url(),
		) );
	}

}
