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
	 * Holds the addons that have been found.
	 *
	 * @var array
	 */
	public $addons = [];

	/**
	 * Get things started.
	 */
	public function __construct() {

		$this->api    = 'http://wpum.test/wp-json/wp/v2/edd-addons';
		$this->addons = $this->get_addons();

		$this->hooks();

	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', [ $this, 'add_addons_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'scripts' ] );
	}

	/**
	 * Retrieve the addons from the api.
	 *
	 * @return array
	 */
	private function get_addons() {

		$addons = [];

		if( isset( $_GET['page'] ) && $_GET['page'] == 'wpum-addons' ) {

			$cached_feed = get_transient( 'wpum_addons_feed' );

			if ( $cached_feed ) {
				$this->addons = $cached_feed;
			} else {
				$feed = wp_remote_get( $this->api, array( 'sslverify' => false ) );
				if ( ! is_wp_error( $feed ) ) {
					$feed_content = wp_remote_retrieve_body( $feed );
					set_transient( 'wpum_addons_feed', $feed_content, 3600 );
					$addons = json_decode( $feed_content );
				}
			}

		}

		return $addons;

	}

	/**
	 * Load the styling required for the addons page.
	 *
	 * @return void
	 */
	public function scripts() {

		$screen = get_current_screen();

		if( $screen->base == 'users_page_wpum-addons' ) {
			wp_enqueue_style( 'wpum-addons', WPUM_PLUGIN_URL . 'assets/css/admin/addons.css', false, WPUM_VERSION );
		}

	}

	/**
	 * Register the new admin menu page.
	 *
	 * @return void
	 */
	public function add_addons_page() {
		add_users_page( esc_html__( 'WP User Manager Add-ons' ), esc_html__( 'Add-ons' ), 'manage_options', 'wpum-addons', [ $this, 'view_addons' ] );
	}

	/**
	 * The function that displays the content of the addons page within the users menu.
	 *
	 * @return void
	 */
	public function view_addons() {

		include WPUM_PLUGIN_DIR . 'includes/wpum-admin/views/addons.php';

	}

}

new WPUM_Addons_Page();
