<?php
/**
 * Handles the display of logout link generator.
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
class WPUM_Shortcode_Logout_Link extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Logout link', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Logout link', 'wp-user-manager' );
		parent::__construct( 'wpum_logout' );
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
				'value' => 'Logout',
				'label' => esc_html__( 'Logout link label', 'wp-user-manager' ),
			),
		);
	}

}

new WPUM_Shortcode_Logout_Link();
