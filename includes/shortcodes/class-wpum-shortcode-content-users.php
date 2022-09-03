<?php
/**
 * Handles the display of the user specific restricted content shortcode generator.
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
 * WPUM_Shortcode_Content_Users
 */
class WPUM_Shortcode_Content_Users extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Users specific content', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Users specific content', 'wp-user-manager' );
		parent::__construct( 'wpum_restrict_to_users' );
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
				'name'    => 'ids',
				'label'   => esc_html__( 'Comma separated user id(s)', 'wp-user-manager' ),
				'tooltip' => esc_html__( 'List of user ids for which the content will be available.', 'wp-user-manager' ),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'show_message',
				'label'   => esc_html__( 'Show message', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
		);
	}

}

new WPUM_Shortcode_Content_Users();
