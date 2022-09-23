<?php
/**
 * Handles the license activation settings for each addon.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

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
		add_action( 'admin_enqueue_scripts', array( $this, 'license_scripts' ) );
		add_action( 'carbon_fields_register_fields', array( $this, 'license_settings_panel' ) );
		add_action( 'admin_notices', array( $this, 'notices' ) );
		add_action( 'admin_footer', array( $this, 'remove_query_args' ) );
	}

	/**
	 * Register the action panel.
	 *
	 * @return void
	 */
	public function license_settings_panel() {

		$settings = $this->get_registered_fields();

		if ( ! empty( $settings ) ) {
			Container::make( 'theme_options', esc_html__( 'WP User Manager addon licenses', 'wp-user-manager' ) )
			->set_page_parent( 'options-general.php' )
			->set_page_menu_title( esc_html__( 'WPUM Licenses', 'wp-user-manager' ) )
			->set_page_file( 'wpum-licenses' )
			->add_fields( $settings );
		}

	}

	/**
	 * Retrieve a list of settings for the licenses.
	 *
	 * @return array
	 */
	private function get_registered_fields() {

		$settings = apply_filters( 'wpum_licenses_register_addon_settings', array() );

		if ( ! empty( $settings ) ) {
			$settings[] = Field::make( 'hidden', 'wpum_license_submission' );
		}

		return $settings;

	}

	/**
	 * Load custom styling on the licensing settings page.
	 *
	 * @return void
	 */
	public function license_scripts() {
		$screen = get_current_screen();
		if ( 'settings_page_wpum-licenses' === $screen->base ) {
			wp_enqueue_style( 'wpum-license-styles', WPUM_PLUGIN_URL . 'assets/css/admin/licensing.css', false, WPUM_VERSION );
		}
	}

	/**
	 * Display a notice about the status of licenses.
	 *
	 * @return void
	 */
	public function notices() {
		$license = filter_input( INPUT_GET, 'license', FILTER_SANITIZE_STRING );
		if ( is_admin() && current_user_can( 'manage_options' ) && 'deactivated' === $license ) {

			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php esc_html_e( 'License successfully deactivated.', 'wp-user-manager' ); ?></strong></p>
			</div>
			<?php

		}

	}

	/**
	 * Remove url query arguments when on the license page.
	 * Right now only removes the "license" argument.
	 *
	 * @return void
	 */
	public function remove_query_args() {

		$screen = get_current_screen();
		if ( 'settings_page_wpum-licenses' !== $screen->base ) {
			return;
		}

		?>
		<script>
			var wpum_location = jQuery( location );

			window.wpum_removeArguments = function() {
				function removeParam(key, sourceURL) {
					var rtn = sourceURL.split("?")[0],
						param, params_arr = [],
						queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
					if (queryString !== "") {
						params_arr = queryString.split("&");
						for (var i = params_arr.length - 1; i >= 0; i -= 1) {
								param = params_arr[i].split("=")[0];
								if (jQuery.inArray(param, key) > -1) {
										params_arr.splice(i, 1);
								}
						}
						rtn = rtn + "?" + params_arr.join("&");
					}
					return rtn;
				}

				var remove_query_args = ['license'];

				url = wpum_location.attr('href');
				url = removeParam(remove_query_args, url);

				if (typeof history.replaceState === 'function') {
					history.replaceState({}, '', url);
				}
			};

			window.wpum_removeArguments();
		</script>
		<?php

	}

}

new WPUM_Updater_Settings();
