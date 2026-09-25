<?php
/**
 * Handles the display of user directory to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * User directory widget
 */
class WPUM_UserDirectory extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_user_directory';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-editor-list-ol';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'users',
		'user',
		'directory',
		'user directory',
		'user group',
		'group',
	);

	/**
	 * @var string
	 */
	public function get_name() {
		return 'user-directory';
	}

	/**
	 * @var string
	 */
	public function get_title() {
		return esc_html__( 'User Directory', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'id',
				'attributes' => array(
					'label'   => esc_html__( 'Select Directory', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => '',
					'options' => $this->get_directories(),
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public function get_directories() {
		$directories = array( '' => __( 'Select User Directory', 'wp-user-manager' ) );

		$posts = get_posts(
			array(
				'status'    => 'publish',
				'order'     => 'ASC',
				'post_type' => 'wpum_directory',
			)
		);

		foreach ( $posts as $post ) {
			$directories[ $post->ID ] = $post->post_title;
		}

		return apply_filters( 'wpum_get_directories', $directories );
	}
}
