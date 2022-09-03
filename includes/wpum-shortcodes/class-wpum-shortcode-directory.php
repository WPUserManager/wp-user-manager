<?php
/**
 * Handles the display of the directory shortcode generator.
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
 * WPUM_Shortcode_Directory
 */
class WPUM_Shortcode_Directory extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Directory', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Directory', 'wp-user-manager' );
		parent::__construct( 'wpum_user_directory' );
	}

	/**
	 * Setup fields for the login shortcode window.
	 *
	 * @return array
	 */
	public function define_fields() {
		return array(
			array(
				'type'  => 'textbox',
				'name'  => 'id',
				'label' => esc_html__( 'Directory ID', 'wp-user-manager' ),
			),
		);
	}

}

new WPUM_Shortcode_Directory();
