<?php
/**
 * Handles the display of profile shortcode generator.
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
class WPUM_Shortcode_Profile extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Profile page', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Profiles page', 'wp-user-manager' );
		parent::__construct( 'wpum_profile' );
	}

}

new WPUM_Shortcode_Profile();
