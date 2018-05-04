<?php
/**
 * Handles registration and display of the add-ons related pages.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Addons_Page {

	/**
	 * API Url from where to retrieve the addons.
	 *
	 * @var string
	 */
	public $api;

	/**
	 * Get things started.
	 */
	public function __construct() {

		$this->api = 'http://wpum.test/wp-json/wp/v2/edd-addons';

		$this->hooks();

	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', [ $this, 'add_addons_page' ] );
	}

	/**
	 * Register the new admin menu page.
	 *
	 * @return void
	 */
	public function add_addons_page() {
		add_users_page( esc_html__( 'WP User Manager Addons' ), esc_html__( 'Addons' ), 'manage_options', 'wpum-addons', [ $this, 'view_addons' ] );
	}

	/**
	 * The function that displays the content of the addons page within the users menu.
	 *
	 * @return void
	 */
	public function view_addons() {

	}

}

new WPUM_Addons_Page();
