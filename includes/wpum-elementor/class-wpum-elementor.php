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
		add_action( 'elementor/preview/enqueue_styles', function() {
			wp_enqueue_script( 'wpum-preview-script', WPUM_PLUGIN_URL . 'assets/js/admin/elementor-preview.min.js' , array(), WPUM_VERSION, true );
		} );

		$this->register_tab_visibility_control();

		$this->detect_visibility_for_widgets();

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
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/widgets/single-profile-tabs-content.php';

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

	/**
	 * Retrieve options for the profile tabs visiblity option.
	 *
	 * @return array
	 */
	private function get_registered_profile_tabs() {

		$registered_tabs = wpum_get_registered_profile_tabs();

		$tabs = [];

		foreach ( $registered_tabs as $key => $tab ) {
			$tabs[ $key ] = $tab['name'];
		}

		return $tabs;

	}

	/**
	 * Register a setting for all widgets so it can be determined in which profile tab,
	 * some widgets should be displayed.
	 *
	 * @return void
	 */
	public function register_tab_visibility_control() {

		$page_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : false;

		if( $page_id !== absint( wpum_get_core_page_id( 'profile' ) ) ) {
			return false;
		}

		$this->register_simulation_control();

		add_action( 'elementor/element/after_section_end', function( $element, $section_id, $args ) {
			if ( '_section_style' === $section_id ) {

				$element->start_controls_section(
					'profile_tab_visibility_section',
					[
						'label' => esc_html__( 'Profile tab visibility' ),
						'tab' => \Elementor\Controls_Manager::TAB_ADVANCED
					]
				);

				$element->add_control(
					'selected_visible_tabs',
					[
						'label'       => esc_html__( 'Select profile tabs' ),
						'label_block' => true,
						'description' => esc_html__( 'Select on which profile tabs this element should be displayed.' ),
						'type'        => \Elementor\Controls_Manager::SELECT2,
						'multiple'    => true,
						'options'     => $this->get_registered_profile_tabs()
					]
				);

				$element->end_controls_section();

			}
		}, 10, 3 );

	}

	/**
	 * Register the profile tag simulation control.
	 *
	 * When a tab is switched, the preview is refreshed and it tells WPUM
	 * to show a different profile tab.
	 *
	 * @return void
	 */
	private function register_simulation_control() {

		add_action( 'elementor/documents/register_controls', function( $element ) {

			$element->start_controls_section(
				'simulate_profile_tab',
				[
					'label' => esc_html__( 'Profile tab simulation' ),
					'tab' => \Elementor\Controls_Manager::TAB_SETTINGS
				]
			);

			$element->add_control(
				'simulated_tab',
				[
					'label'       => esc_html__( 'Simulate profile tab' ),
					'label_block' => true,
					'description' => esc_html__( 'Select a profile tab, to simulate it\'s activation. This allows you to add elements specific to that profile tab.' ),
					'type'        => \Elementor\Controls_Manager::SELECT,
					'options'     => $this->get_registered_profile_tabs()
				]
			);

			$element->end_controls_section();

		}, 10 );

	}

	/**
	 * Detect the visibility assigned to the widget.
	 *
	 * @return string
	 */
	public function detect_visibility_for_widgets() {

		add_action( 'elementor/widget/render_content', function( $content, $widget ) {

			// Bail out if not on the profile page.
			$page_id = get_the_ID();
			if( $page_id && absint( $page_id ) !== absint( wpum_get_core_page_id( 'profile' ) ) ) {
				return $content;
			}

			if( ! \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				// Retrieve registered visibility setting.
				$settings   = $widget->get_settings();
				$visibility = isset( $settings[ 'selected_visible_tabs' ] ) ? $settings[ 'selected_visible_tabs' ] : false;

				// Trigger the visibility on the frontend.
				if( $visibility && is_array( $visibility ) && ! empty( $visibility ) ) {
					$active_profile_tab = wpum_get_active_profile_tab();
					if( ! in_array( $active_profile_tab, $visibility ) ) {
						return false;
					}
				}

			}

			// Adjust visibility within the editor mode.
			/*if( \Elementor\Plugin::instance()->editor->is_edit_mode() ) {
				$page                   = \Elementor\PageSettings\Manager::get_page( $page_id );
				$editor_active_tab      = $page->get_settings( 'simulated_tab' );
				$editor_widget_settings = $widget->get_settings();
				$editor_visibility      = isset( $editor_widget_settings[ 'selected_visible_tabs' ] ) ? $editor_widget_settings[ 'selected_visible_tabs' ]: false;

				if( is_array( $editor_visibility ) && ! empty( $editor_visibility ) ) {
					return '<div class="wpum-profile-visibility-indicator"><span>' . $editor_visibility[0] . '</span>' . $content . '</div>';
				}

			}*/

			return $content;
		}, 10, 2 );

	}

}

new WPUM_Elementor;
