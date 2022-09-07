<?php
/**
 * Handles the display of recently registered users to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

class RecentlyRegisteredUsers extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_recently_registered';

	public function get_name() {
		return 'recently-registered-users';
	}

	public function get_title() {
		return esc_html__( 'Recently Registered', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_categories() {
		return array( 'wp-user-manager' );
	}

	public function get_keywords() {
		return array(
			esc_html__( 'users', 'wp-user-manager' ),
			esc_html__( 'user', 'wp-user-manager' ),
			esc_html__( 'registered', 'wp-user-manager' ),
			esc_html__( 'recently registered', 'wp-user-manager' ),
			esc_html__( 'recently registered users', 'wp-user-manager' ),
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
			'amount',
			array(
				'label' => esc_html__( 'How many users to display', 'wp-user-manager' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'link_to_profile',
			array(
				'label'        => esc_html__( 'Show profile link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}
