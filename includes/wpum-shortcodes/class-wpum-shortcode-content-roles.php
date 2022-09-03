<?php
/**
 * Handles the display of user roles specific content shortcode generator.
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
 * WPUM_Shortcode_Content_Roles
 */
class WPUM_Shortcode_Content_Roles extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Specific roles only content', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Specific roles only content', 'wp-user-manager' );
		parent::__construct( 'wpum_restrict_to_user_roles' );
	}

	/**
	 * Setup fields for the shortcode window.
	 *
	 * @return array
	 */
	public function define_fields() {
		return array(
			array(
				'type'    => 'textbox',
				'name'    => 'roles',
				'label'   => esc_html__( 'Comma separated user role(s)', 'wp-user-manager' ),
				'tooltip' => esc_html__( 'List of user roles for which the content will be available.', 'wp-user-manager' ),
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

new WPUM_Shortcode_Content_Roles();
