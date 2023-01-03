<?php
/**
 * Handles the display of login form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Login form widget
 */
class WPUM_LoginForm extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_login_form';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-user-circle-o';

	/**
	 * @return array
	 */
	public function get_title() {
		return esc_html__( 'Login Form', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'login', 'wp-user-manager' ),
			esc_html__( 'login form', 'wp-user-manager' ),
		);
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'login_link',
				'attributes' => array(
					'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
			array(
				'id'         => 'register_link',
				'attributes' => array(
					'label'        => esc_html__( 'Show registration link', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
		);
	}
}
