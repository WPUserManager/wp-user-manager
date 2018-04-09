<?php
/**
 * Handles the registration forms editor.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Registration_Forms_Editor {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
	}

	/**
	 * Add the registration forms editor link to the admin dashboard menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Registration Forms Editor' ),
			esc_html__( 'Registration forms' ),
			'manage_options',
			'wpum-registration-forms',
			[ $this, 'display_registration_forms_editor' ]
		);
	}

	/**
	 * Registration forms editor page. Display is handled by Vuejs.
	 *
	 * @return void
	 */
	public function display_registration_forms_editor() {
		echo '<div class="wrap"><div id="wpum-registration-forms-editor"></div></div>';
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if( $screen->base == 'users_page_wpum-registration-forms' ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-registration-forms-editor', 'http://localhost:8080/registration-forms-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_die( 'Vue build missing' );
			}

			wp_enqueue_script( 'wpum-registration-forms-editor' );
			wp_enqueue_style( 'wpum-registration-forms-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css' , array(), WPUM_VERSION );

			$js_variables = [
				'labels'    => $this->get_labels(),
				'pluginURL' => WPUM_PLUGIN_URL,
			];

			wp_localize_script( 'wpum-registration-forms-editor', 'wpumRegistrationFormsEditor', $js_variables );

		}

	}

	private function get_labels() {

		$labels = [
			'page_title' => esc_html__( 'WP User Manager Registration Forms Editor' )
		];

		return $labels;

	}

}

$wpum_registration_forms_editor = new WPUM_Registration_Forms_Editor;
