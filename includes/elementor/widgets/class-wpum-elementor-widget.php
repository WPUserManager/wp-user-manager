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
	protected $shortcode_function;

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
		$name = str_replace( 'wpum_', '', $this->shortcode_function );

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
	 * Render
	 */
	public function render() {
		$attributes = $this->get_settings_for_display();
		$output     = call_user_func( $this->shortcode_function, $attributes );
		$output     = preg_replace( "/<form action=([\"'])(.*?)\"/", '<form action="javascript:void(0);"', $output );

		echo $output;  // phpcs:ignore
	}
}
