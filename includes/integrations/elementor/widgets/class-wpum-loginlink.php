<?php
/**
 * Handles the display of login link to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Login link widget
 */
class WPUM_LoginLink extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_login';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-editor-link';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'login',
		'login link',
	);

	/**
	 * Elementor has its own "login" widget, which the name built from the shortcode would clash with.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'wpum-login-link';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Login Link', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'redirect',
				'attributes' => array(
					'label' => esc_html__( 'URL to redirect to after login', 'wp-user-manager' ),
					'type'  => \Elementor\Controls_Manager::TEXT,
				),
			),
			array(
				'id'         => 'label',
				'attributes' => array(
					'label'   => esc_html__( 'Link label', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => 'Login',
				),
			),
		);
	}
}
