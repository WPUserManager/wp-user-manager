<?php
/**
 * Handles the display of profile card to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

class ProfileCard extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_profile_card';

	public function get_name() {
		return 'profile-card';
	}

	public function get_title() {
		return esc_html__( 'Profile card', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}
	
	public function get_categories() {
		return [ 'wp-user-manager' ];
	}

	public function get_keywords() {
		return [
			esc_html__( 'profile', 'wp-user-manager' ),
			esc_html__( 'user', 'wp-user-manager' ),
			esc_html__( 'card', 'wp-user-manager' ),
			esc_html__( 'profile card', 'wp-user-manager' ),
			esc_html__( 'user profile', 'wp-user-manager' )
		];
	}
	
	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT
			]
		);

		$this->add_control(
			'user_id',
			[
				'label'   => esc_html__( 'Select User', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => get_current_user_id(),
				'options' => $this->get_users()
			]
		);

		$this->add_control(
			'link_to_profile',
			[
				'label'        => esc_html__( 'Profile link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes'
			]
		);

		$this->add_control(
			'display_buttons',
			[
				'label'        => esc_html__( 'Show buttons', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes'
			]
		);

		$this->add_control(
			'display_cover',
			[
				'label'        => esc_html__( 'Display profile cover', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes'
			]
		);
		
		$this->end_controls_section();
	}
	
	public function get_users() {
		$users = [];
		
		foreach( get_users() as $user ) {
			$users[ $user->ID ] = $user->user_login;
		}

		$users = apply_filters( 'wpum_users_profile_card', $users );
		
		return $users;
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}