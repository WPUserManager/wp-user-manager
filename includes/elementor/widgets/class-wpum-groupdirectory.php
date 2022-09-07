<?php
/**
 * Handles the display of group directory form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

class GroupDirectory extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_group_directory';

	public function get_name() {
		return 'group-directory';
	}

	public function get_title() {
		return esc_html__( 'Group Directory', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-posts-group';
	}

	public function get_categories() {
		return array( 'wp-user-manager' );
	}

	public function get_keywords() {
		return array(
			esc_html__( 'login', 'wp-user-manager' ),
			esc_html__( 'login form', 'wp-user-manager' ),
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
			'per_page',
			array(
				'label'   => esc_html__( 'Groups per page', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '10',
			)
		);

		$this->add_control(
			'has_search_form',
			array(
				'label'        => esc_html__( 'Show search form', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'show_public',
			array(
				'label'        => esc_html__( 'Show public groups only', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->add_control(
			'show_private',
			array(
				'label'        => esc_html__( 'Show private groups only', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'no',
			)
		);

		$this->end_controls_section();
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}
