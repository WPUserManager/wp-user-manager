<?php
/**
 * Plugin Name: WP User Manager
 * Plugin URI:  https://wpusermanager.com
 * Description: Beautifully simple user profile directories with frontend login, registration and account customization. WP User Manager is the best solution to manage your community and your users for WordPress.
 * Version:     2.8.7
 * Author:      WP User Manager
 * Author URI:  https://wpusermanager.com
 * License:     GPLv3+
 * Text Domain: wp-user-manager
 * Domain Path: /languages
 **/

/**
 * WP User Manager.
 *
 * Copyright (c) 2020 WP User Manager
 *
 * WP User Manager. is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP User Manager. is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @author         WP User Manager
 * @version        2.3.6
 * @copyright  (c) 2020 WP User Manager
 * @license        http://www.gnu.org/licenses/gpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 * @package        wp-user-manager
 */

/**
 * Start WP User Manager.
 * The main function responsible for returning the one true WP User Manager instance to functions everywhere.
 *
 * @return WP_User_Manager
 */
function WPUM() {
	require_once dirname( __FILE__ ) . '/includes/class-wp-user-manager.php';

	return WP_User_Manager::instance( __FILE__ );
}

WPUM();
