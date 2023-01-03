<?php
/**
 * Handles the display of group directory form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Group Directory widget
 */
class WPUM_GroupDirectory extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_group_directory';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-posts-group';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'wpum group',
		'group directory',
	);

	/**
	 * @var string
	 */
	public function get_title() {
		return esc_html__( 'Group Directory', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'per_page',
				'attributes' => array(
					'label'   => esc_html__( 'Groups per page', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => '10',
				),
			),
			array(
				'id'         => 'has_search_form',
				'attributes' => array(
					'label'        => esc_html__( 'Show search form', 'wp-user-manager' ),
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
