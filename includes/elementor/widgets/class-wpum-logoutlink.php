<?php
/**
 * Handles the display of logout link to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

class LogoutLink extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_logout_link';

	public function get_name() {
		return 'logout-link';
	}

	public function get_title() {
		return esc_html__( 'Logout Link', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-editor-unlink';
	}

	public function get_categories() {
		return array( 'wp-user-manager' );
	}

	public function get_keywords() {
		return array(
			esc_html__( 'logout', 'wp-user-manager' ),
			esc_html__( 'logout link', 'wp-user-manager' ),
		);
	}

	protected function register_controls() {
		$this->start_controls_section(
			'wpum_content_section',
			array(
				'label' => esc_html__( 'Settings', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'redirect',
			array(
				'label' => esc_html__( 'URL to redirect to after logout', 'wp-user-manager' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Link label', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Logout',
			)
		);

		$this->end_controls_section();
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}