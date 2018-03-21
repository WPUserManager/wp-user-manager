<?php
/**
 * Handles all the custom fields related functionalities in the backend.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that handles the fields editor.
 */
class WPUM_Fields_Editor {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	private function init_hooks() {
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
			esc_html__( 'WP User Manager Fields Editor' ),
			esc_html__( 'Custom fields' ),
			'manage_options',
			'wpum-custom-fields',
			[ $this, 'display_fields_editor' ]
		);
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if( $screen->base == 'users_page_wpum-custom-fields' ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-fields-editor', 'http://localhost:8080/fields-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_die( 'Vue build missing' );
			}

			wp_enqueue_script( 'wpum-fields-editor' );

			$js_variables = [
				'page_title' => esc_html__( 'WP User Manager Fields Editor' )
			];

			wp_localize_script( 'wpum-fields-editor', 'wpumFieldsEditor', $js_variables );

		}

	}

	/**
	 * Display the fields editor within the admin panel.
	 *
	 * @return void
	 */
	public function display_fields_editor() {
		$test = new WPUM_Field_Group( 3 );
		echo '<div class="wrap"><div id="wpum-fields-editor"></div></div>';
	}

}

new WPUM_Fields_Editor;
