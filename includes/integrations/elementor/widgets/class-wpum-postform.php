<?php
/**
 * Handles the display of front-end posting form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Post form widget
 */
class WPUM_PostForm extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_post_form';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-post-content';


	/**
	 * @var array
	 */
	protected $keywords = array(
		'post form',
		'post',
		'form',
	);

	/**
	 * @return string
	 */
	public function get_name() {
		return 'post-form';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Post Form', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		$post_forms = $this->get_post_forms();
		$default    = 0;

		if ( ! empty( $post_forms ) ) {
			$default = array_key_first( $post_forms );
		}

		return array(
			array(
				'id'         => 'form_id',
				'attributes' => array(
					'label'   => esc_html__( 'Select Form', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => $default,
					'options' => $post_forms,
				),
			),
		);
	}

	/**
	 * @return array
	 */
	protected function get_post_forms() {
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
}
