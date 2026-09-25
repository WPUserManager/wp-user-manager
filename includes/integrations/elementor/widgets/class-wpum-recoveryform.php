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
	protected $shortcode = 'wpum_password_recovery';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-lock';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'password',
		'recovery',
		'password recovery',
		'forgot password',
		'forgot',
	);

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
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'login_link',
				'attributes' => array(
					'label'        => esc_html__( 'Show login link', 'wp-user-manager' ),
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
