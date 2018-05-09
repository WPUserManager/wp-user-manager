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

		$settings = [];
		$registered_addons = apply_filters( 'wpum_licenses_register_addon', [] );

		return $settings;

	}

}

new WPUM_Updater_Settings;
