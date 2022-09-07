<?php
/**
 * Handles the display of user directory to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

class UserDirectory extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_directory';

	public function get_name() {
		return 'user-directory';
	}

	public function get_title() {
		return esc_html__( 'User Directory', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-editor-list-ol';
	}

	public function get_categories() {
		return array( 'wp-user-manager' );
	}

	public function get_keywords() {
		return array(
			esc_html__( 'users', 'wp-user-manager' ),
			esc_html__( 'user', 'wp-user-manager' ),
			esc_html__( 'directory', 'wp-user-manager' ),
			esc_html__( 'user directory', 'wp-user-manager' ),
			esc_html__( 'user group', 'wp-user-manager' ),
			esc_html__( 'group', 'wp-user-manager' ),
		);
	}

	protected function register_controls() {

		$this->start_controls_section(
			'wpum_content_section',
			array(
				'label' => esc_html__( 'Settings', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'id',
			array(
				'label'   => esc_html__( 'Select Directory', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => '',
				'options' => $this->get_directories(),
			)
		);

		$this->end_controls_section();
	}

	public function get_directories() {
		$directories = array( '' => 'Select Directory' );

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

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}
