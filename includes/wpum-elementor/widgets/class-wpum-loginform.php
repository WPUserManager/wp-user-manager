<?php
/**
 * Handles the display of login form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

class LoginForm extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_login_form';

	public function get_name() {
		return 'login-form';
	}

	public function get_title() {
		return esc_html__( 'Login Form', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-user-circle-o';
	}
	
	public function get_categories() {
		return [ 'wp-user-manager' ];
	}

	public function get_keywords(){
		return [
			esc_html__( 'login', 'wp-user-manager' ),
			esc_html__( 'login form', 'wp-user-manager' )
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'login_link',
			[
				'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'register_link',
			[
				'label'        => esc_html__( 'Show registration link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}