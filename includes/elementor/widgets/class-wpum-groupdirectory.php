<?php
/**
 * Handles the display of group directory form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Group Directory widget
 */
class WPUM_GroupDirectory extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_group_directory';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-posts-group';

	/**
	 * @var string
	 */
	public function get_title() {
		return esc_html__( 'Group Directory', 'wp-user-manager' );
	}

	/**
	 * @var array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'login', 'wp-user-manager' ),
			esc_html__( 'login form', 'wp-user-manager' ),
		);
	}

	/**
	 * Register
	 */
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
}
