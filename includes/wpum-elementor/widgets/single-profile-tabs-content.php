<?php
/**
 * Single profile navigation tabs content.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/
namespace Elementor;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Elementor_Single_Profile_Tabs_Content extends Widget_Base {

	/**
	 * Retrieve widget name.
	 *
	 * @access public
	 * @return string Widget name.
	 */
    public function get_name() {
        return 'wpum-single-profile-tabs-content';
    }

    /**
	 * Retrieve widget title.
	 *
	 * @access public
	 * @return string Widget title.
	 */
    public function get_title() {
        return esc_html__( 'Profile Tabs Content' );
    }

    /**
	 * Retrieve the list of categories thewidget belongs to.
	 * Used to determine where to display the widget in the editor.
	 *
	 * @access public
	 * @return array Widget categories.
	 */
    public function get_categories() {
        return [ 'wp-user-manager' ];
	}

	/**
	 * Get widget icon.
	 * Retrieve widget icon.
	 *
	 * @access public
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'wpum-logo-font-icon';
	}

	/**
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @access protected
	 */
	protected function _register_controls() {

	}

	/**
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @access protected
	 */
	protected function render() {

		$settings   = $this->get_settings_for_display();
		$user_id    = wpum_get_queried_user_id();
		$user       = get_user_by( 'id', $user_id );
		$active_tab = wpum_get_active_profile_tab();

		WPUM()->templates
			->set_template_data( [
				'user'     => $user,
				'settings' => $settings
			] )
			->get_template_part( "profiles/{$active_tab}" );

	}

}

Plugin::instance()->widgets_manager->register_widget_type( new WPUM_Elementor_Single_Profile_Tabs_Content() );
