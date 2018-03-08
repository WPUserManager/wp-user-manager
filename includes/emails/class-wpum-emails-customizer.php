<?php
/**
 * Handles the email templates customizer.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class that handles the email customization functionalities.
 */
class WPUM_Emails_Customizer {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	/**
	 * Add new menu page to the "Users" menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Emails' ),
			esc_html__( 'Emails' ),
			'manage_options',
			'wpum-emails',
			[ $this, 'display_emails_list' ]
		);
	}

	/**
	 * Add the required scripts to the emails editor page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		if( isset( $_GET['page'] ) && $_GET['page'] == 'wpum-emails' ) {
			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-emails-editor', 'http://localhost:8080/emails.js', array(), WPUM_VERSION, true );
				wp_enqueue_script( 'wpum-emails-editor' );
			}
		}

	}

	/**
	 * Display the list of available emails.
	 *
	 * @return string
	 */
	public function display_emails_list() {
		echo '<div class="wrap"><div id="wpum-emails-list"></div></div>';
	}

}

new WPUM_Emails_Customizer;
