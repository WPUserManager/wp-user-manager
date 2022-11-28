<?php
/**
 * Handles the display of profile page to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Profile page widget
 */
class WPUM_ProfilePage extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_profile';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-preferences';

	/**
	 * @return string
	 */
	public function get_name() {
		return 'profile-page';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Profile page', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'profile', 'wp-user-manager' ),
			esc_html__( 'user profile', 'wp-user-manager' ),
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
			'login_link',
			array(
				'label'        => esc_html__( 'Show login link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'psw_link',
			array(
				'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}
}
