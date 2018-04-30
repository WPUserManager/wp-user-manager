<?php
/**
 * Handles integration with the Elementor page builder plugin.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that integration with Elementor.
 */
class WPUM_Elementor {

	/**
	 * Get thing started.
	 */
	public function __construct() {

		$this->init();

	}

	/**
	 * Hook into WordPress and run hooks and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'elementor/elements/categories_registered', [ $this, 'add_new_category' ] );
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'load_elements' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', function() {
    		wp_enqueue_style( 'wpum-elementor', WPUM_PLUGIN_URL . 'assets/css/admin/logo-font.css' );
		} );
	}

	/**
	 * Load all required files.
	 *
	 * @return void
	 */
	public function load_elements() {

		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/widgets/single-profile-avatar.php';
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/widgets/single-profile-cover.php';
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/widgets/single-profile-displayed-name.php';
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/widgets/single-profile-custom-field.php';
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/widgets/single-profile-navigation-tabs.php';

	}

	/**
	 * Add a new widgets category to Elementor.
	 *
	 * @param object $elements_manager
	 * @return void
	 */
	public function add_new_category( $elements_manager ) {

		$elements_manager->add_category(
			'wp-user-manager',
			[
				'title' => esc_html__( 'WP User Manager' ),
				'icon' => 'fa fa-plug',
			]
		);

	}

}

new WPUM_Elementor;
