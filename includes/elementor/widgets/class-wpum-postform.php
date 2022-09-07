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
		return array( 'wp-user-manager' );
	}

	public function get_keywords() {
		return array(
			esc_html__( 'post form', 'wp-user-manager' ),
			esc_html__( 'post', 'wp-user-manager' ),
			esc_html__( 'form', 'wp-user-manager' ),
		);
	}

	protected function register_controls() {

		$post_forms = $this->get_post_forms();
		$default    = 0;

		if ( ! empty( $post_forms ) ) {
			$default = array_key_first( $post_forms );
		}

		$this->start_controls_section(
			'wpum_content_section',
			array(
				'label' => esc_html__( 'Settings', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'form_id',
			array(
				'label'   => esc_html__( 'Select Form', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => $default,
				'options' => $post_forms,
			)
		);

		$this->end_controls_section();
	}

	private function get_post_forms() {
		$post_forms = WPUMFR()->post_forms->get_forms();
		$forms      = array();

		foreach ( $post_forms as $post_form ) {
			$name = $post_form->name;

			if ( ! empty( $post_form->get_settings_model()['form_name'] ) ) {
				$name = $post_form->get_settings_model()['form_name'];
			}

			$forms[ $post_form->ID ] = $name;
		}

		return $forms;
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );
	}
}
