<?php
/**
 * Handles registration and display of the addons related pages.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Addons page
 */
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

		$this->api = 'https://wpusermanager.com/wp-json/wp/v2/edd-addons';
		$this->hooks();

	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'add_addons_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
		add_filter( 'install_plugins_tabs', array( $this, 'add_addon_tab' ) );
		add_action( 'install_plugins_wpum_addons', array( $this, 'view_addons' ) );
	}

	/**
	 * Retrieve the addons from the api.
	 *
	 * @return array
	 */
	public function get_addons() {

		$addons = array();

		$page = filter_input( INPUT_GET, 'page' );
		$tab  = filter_input( INPUT_GET, 'tab' );

		if ( 'wpum-addons' === $page || 'wpum_addons' === $tab ) {

			$cached_feed = get_transient( 'wpum_addons_feed' );

			if ( $cached_feed ) {
				$addons = $cached_feed;
			} else {
				$feed = wp_remote_get( $this->api, array( 'sslverify' => false ) );
				if ( ! is_wp_error( $feed ) ) {
					$feed_content = wp_remote_retrieve_body( $feed );
					$addons       = json_decode( $feed_content );
					set_transient( 'wpum_addons_feed', $addons, 3600 );
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

		$tab = filter_input( INPUT_GET, 'tab' );

		if ( 'users_page_wpum-addons' === $screen->base || 'plugin-install' === $screen->base && 'wpum_addons' === $tab ) {
			wp_enqueue_style( 'wpum-addons', WPUM_PLUGIN_URL . 'assets/css/admin/addons.css', false, WPUM_VERSION );
		}
	}

	/**
	 * Register the new admin menu page.
	 *
	 * @return void
	 */
	public function add_addons_page() {
		add_users_page( esc_html__( 'WP User Manager Addons', 'wp-user-manager' ), esc_html__( 'Addons', 'wp-user-manager' ), apply_filters( 'wpum_admin_pages_capability', 'manage_options' ), 'wpum-addons', array( $this, 'view_addons' ) );
	}

	/**
	 * The function that displays the content of the addons page within the users menu.
	 *
	 * @return void
	 */
	public function view_addons() {

		include WPUM_PLUGIN_DIR . 'includes/admin/views/addons.php';

	}

	/**
	 * Adds a new tab to the install plugins page.
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function add_addon_tab( $tabs ) {
		$tabs['wpum_addons'] = '<img src="' . WPUM_PLUGIN_URL . 'assets/images/logo.svg" alt="WP User Manager" style="width: 15px; margin-right: 10px; vertical-align: text-bottom;">' . __( 'WP User Manager ', 'wp-user-manager' ) . '<span class="wpum-addons">' . __( 'Addons', 'wp-user-manager' ) . '</span><style>.plugin-install-wpum_addons a.current { border-bottom-color: #016afe; }</style>';

		return $tabs;
	}

}

new WPUM_Addons_Page();
