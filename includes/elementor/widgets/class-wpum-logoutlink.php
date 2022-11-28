<?php
/**
 * Handles the display of logout link to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Logout Elementor widget
 */
class WPUM_LogoutLink extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_logout_link';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-editor-unlink';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'logout',
		'logout link',
	);

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Logout Link', 'wp-user-manager' );
	}

	/**
	 * Register controls
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
			'redirect',
			array(
				'label' => esc_html__( 'URL to redirect to after logout', 'wp-user-manager' ),
				'type'  => \Elementor\Controls_Manager::TEXT,
			)
		);

		$this->add_control(
			'label',
			array(
				'label'   => esc_html__( 'Link label', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Logout',
			)
		);

		$this->end_controls_section();
	}
}
