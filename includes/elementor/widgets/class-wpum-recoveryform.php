<?php
/**
 * Handles the display of password recovery form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Recovery password form widget
 */
class WPUM_RecoveryForm extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_password_recovery';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-lock';

	/**
	 * @return string
	 */
	public function get_name() {
		return 'password-recovery-form';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Password recovery form', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'password', 'wp-user-manager' ),
			esc_html__( 'recovery', 'wp-user-manager' ),
			esc_html__( 'password recovery', 'wp-user-manager' ),
			esc_html__( 'forgot password', 'wp-user-manager' ),
			esc_html__( 'forgot', 'wp-user-manager' ),
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
			'register_link',
			array(
				'label'        => esc_html__( 'Show registration link', 'wp-user-manager' ),
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
