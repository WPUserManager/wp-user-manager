<?php
/**
 * Handles the display of login link generator.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add login shortcode window to the editor.
 */
class WPUM_Shortcode_Login_Link extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Login link', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Login link', 'wp-user-manager' );
		parent::__construct( 'wpum_login' );
	}

	/**
	 * Setup fields for the login shortcode window.
	 *
	 * @return array
	 */
	public function define_fields() {
		return array(
			array(
				'type'    => 'textbox',
				'name'    => 'redirect',
				'label'   => esc_html__( 'Redirect to', 'wp-user-manager' ),
				'tooltip' => esc_html__( '(optional) must be a link within this website.', 'wp-user-manager' ),
			),
			array(
				'type'  => 'textbox',
				'name'  => 'label',
				'value' => 'Login',
				'label' => esc_html__( 'Login label', 'wp-user-manager' ),
			),
		);
	}

}

new WPUM_Shortcode_Login_Link();
