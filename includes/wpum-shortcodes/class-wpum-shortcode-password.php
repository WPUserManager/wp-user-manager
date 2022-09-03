<?php
/**
 * Handles the display of the forgot password shortcode generator.
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
class WPUM_Shortcode_Password extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Password recovery form', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Password recovery form', 'wp-user-manager' );
		parent::__construct( 'wpum_password_recovery' );
	}

	/**
	 * Setup fields for the login shortcode window.
	 *
	 * @return array
	 */
	public function define_fields() {
		return array(
			array(
				'type'    => 'listbox',
				'name'    => 'login_link',
				'label'   => esc_html__( 'Show login link:', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'register_link',
				'label'   => esc_html__( 'Show registration link:', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
		);
	}

}

new WPUM_Shortcode_Password();
