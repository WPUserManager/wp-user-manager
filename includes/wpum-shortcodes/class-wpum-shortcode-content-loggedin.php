<?php
/**
 * Handles the display of logged in content shortcode generator.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add registration form shortcode window to the editor.
 */
class WPUM_Shortcode_Content_Loggedin extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Members only content', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Members only content', 'wp-user-manager' );
		parent::__construct( 'wpum_restrict_logged_in' );
	}

}

new WPUM_Shortcode_Content_Loggedin;
