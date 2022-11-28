<?php
/**
 * Handles the display of profile card to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Profile car widget
 */
class WPUM_ProfileCard extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_profile_card';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-call-to-action';

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Profile card', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'profile', 'wp-user-manager' ),
			esc_html__( 'user', 'wp-user-manager' ),
			esc_html__( 'card', 'wp-user-manager' ),
			esc_html__( 'profile card', 'wp-user-manager' ),
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
			'user_id',
			array(
				'label'   => esc_html__( 'Select User', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => get_current_user_id(),
				'options' => $this->get_users(),
			)
		);

		$this->add_control(
			'link_to_profile',
			array(
				'label'        => esc_html__( 'Profile link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'display_buttons',
			array(
				'label'        => esc_html__( 'Show buttons', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'display_cover',
			array(
				'label'        => esc_html__( 'Display profile cover', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Get Users
	 *
	 * @return array
	 */
	public function get_users() {
		$users = array();

		foreach ( get_users() as $user ) {
			$users[ $user->ID ] = $user->user_login;
		}

		$users = apply_filters( 'wpum_users_profile_card', $users );

		return $users;
	}
}
