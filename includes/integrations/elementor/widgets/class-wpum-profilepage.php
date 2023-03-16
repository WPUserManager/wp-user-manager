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
	protected $shortcode = 'wpum_profile';

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
	 * @var array
	 */
	protected $keywords = array(
		'profile',
		'user profile',
	);

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Profile page', 'wp-user-manager' );
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
				'id'         => 'psw_link',
				'attributes' => array(
					'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
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
