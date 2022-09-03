<?php
/**
 * Handles the display of account form shortcode generator.
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
 * Add registration form shortcode window to the editor.
 */
class WPUM_Shortcode_My_Account extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Account page', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Account page', 'wp-user-manager' );
		parent::__construct( 'wpum_account' );
	}

}

new WPUM_Shortcode_My_Account();
