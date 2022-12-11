<?php
/**
 * Handles the display of account form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Abstract Elementor
 */
abstract class WPUM_Elementor_Widget extends \Elementor\Widget_Base {

	/**
	 * @var string
	 */
	protected $shortcode;

	/**
	 * @var string
	 */
	protected $icon;

	/**
	 * @var array
	 */
	protected $keywords = array();

	/**
	 * @return string
	 */
	public function get_name() {
		$name = str_replace( 'wpum_', '', $this->shortcode );

		return str_replace( '_', '-', $name );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return $this->icon;
	}

	/**
	 * @return array
	 */
	public function get_categories() {
		return array( 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array_map( 'esc_html', $this->keywords );
	}

	/**
	 * WPUM widget controls
	 */
	public function widget_controls() {
		return array();
	}

	/**
	 * Register WPUM Controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'wpum_content_section',
			array(
				'label' => esc_html__( 'Settings', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			),
		);

		foreach ( $this->widget_controls() as $control ) {
			$this->add_control(
				$control['id'],
				$control['attributes'],
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Generate shortcode string.
	 */
	public function generate_shortcode_string() {
		$settings     = $this->get_settings_for_display();
		$control_keys = array_column( $this->widget_controls(), 'id' );
		$atttributes  = '';

		foreach ( $control_keys as $control_key ) {
			$atttributes .= $control_key . '=' . $settings[ $control_key ] . ' ';
		}

		return "[$this->shortcode $atttributes]";
	}

	/**
	 * Render shortcode output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 */
	protected function render() {
		$shortcode = do_shortcode( shortcode_unautop( $this->generate_shortcode_string() ) );
		$output    = preg_replace( "/<form action=([\"'])(.*?)\"/", '<form action="javascript:void(0);"', $shortcode );
		echo $output; // phpcs:ignore
	}

	/**
	 * Render shortcode widget as plain content.
	 *
	 * Override the default behavior by printing the shortcode instead of ren-dering it.
	 */
	public function render_plain_content() {
		$shortcode = $this->generate_shortcode_string();
		echo $shortcode; // phpcs:ignore
	}
}
