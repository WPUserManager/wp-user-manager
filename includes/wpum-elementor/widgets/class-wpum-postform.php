<?php
/**
 * Handles the display of front-end posting form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

class PostForm extends \Elementor\Widget_Base {
	
	protected $shortcode_function = 'wpumfr_post_form';

	public function get_name() {
		return 'post-form';
	}

	public function get_title() {
		return esc_html__( 'Post Form', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-post-content';
	}
	
	public function get_categories() {
		return [ 'wp-user-manager' ];
	}

	public function get_keywords(){
		return [
			esc_html__( 'post form', 'wp-user-manager' ),
			esc_html__( 'post', 'wp-user-manager' ),
			esc_html__( 'form', 'wp-user-manager' )
		];
	}

	protected function register_controls() {

		$post_forms = WPUMFR()->post_forms->get_forms();
		$default    = 0;

		if ( isset( $post_forms[0])) {
			$default = $post_forms[0]->ID;
		}

		$this->start_controls_section(
			'content_section',
			[
				'label' => esc_html__( 'Content', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'form_id',
			[
				'label'   => esc_html__( 'Select Form', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $default,
				'options' => $post_forms
			]
		);

		$this->end_controls_section();
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}