<?php
/**
 * Handles the license activation settings for each addon.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * The class that handles registration of the license activation settings.
 */
class WPUM_Updater_Settings {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hook into WP.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'license_scripts' ] );
		add_action( 'carbon_fields_register_fields', [ $this, 'license_settings_panel' ] );
	}

	/**
	 * Register the action panel.
	 *
	 * @return void
	 */
	public function license_settings_panel() {
		Container::make( 'theme_options', esc_html__( 'WP User Manager add-ons licenses' ) )
			->set_page_parent( 'options-general.php' )
			->set_page_menu_title( esc_html__( 'WPUM Licenses' ) )
			->set_page_file( 'wpum-licenses' )
			->add_fields( $this->get_registered_fields() );
	}

	/**
	 * Retrieve a list of settings for the licenses.
	 *
	 * @return array
	 */
	private function get_registered_fields() {

		$settings = apply_filters( 'wpum_licenses_register_addon_settings', [] );

		$settings[] = Field::make( 'hidden', 'wpum_license_submission' );

		return $settings;

	}

	/**
	 * Load custom styling on the licensing settings page.
	 *
	 * @return void
	 */
	public function license_scripts() {
		$screen = get_current_screen();
		if( $screen->base == 'settings_page_wpum-licenses' ) {
			wp_enqueue_style( 'wpum-license-styles', WPUM_PLUGIN_URL . 'assets/css/admin/licensing.css', false, WPUM_VERSION );
		}
	}

}

new WPUM_Updater_Settings;
