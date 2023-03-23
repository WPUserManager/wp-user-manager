<?php
/**
 * Plugin Name: WP User Manager
 * Plugin URI:  https://wpusermanager.com
 * Description: Beautifully simple user profile directories with frontend login, registration and account customization. WP User Manager is the best solution to manage your community and your users for WordPress.
 * Version:     2.9.7
 * Requires PHP: 7.2
 * Author:      WP User Manager
 * Author URI:  https://wpusermanager.com
 * License:     GPLv3+
 * Text Domain: wp-user-manager
 * Domain Path: /languages
 **/

/**
 * Start WP User Manager.
 * The main function responsible for returning the one true WP User Manager instance to functions everywhere.
 *
 * @return WP_User_Manager
 */
function WPUM() {
	require_once dirname( __FILE__ ) . '/includes/class-wp-user-manager.php';

	return WP_User_Manager::instance( __FILE__, '2.9.7' );
}

WPUM();
