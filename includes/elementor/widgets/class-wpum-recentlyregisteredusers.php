<?php
/**
 * Handles the display of recently registered users to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Recently registered widget
 */
class WPUM_RecentlyRegisteredUsers extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_recently_registered';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-person';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'user',
		'users',
		'registered',
		'recently registered',
	);

	/**
	 * @return string
	 */
	public function get_name() {
		return 'recently-registered-users';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Recently Registered', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'amount',
				'attributes' => array(
					'label'   => esc_html__( 'How many users to display', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => 10,
				),
			),
			array(
				'id'         => 'link_to_profile',
				'attributes' => array(
					'label'        => esc_html__( 'Show profile link', 'wp-user-manager' ),
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
