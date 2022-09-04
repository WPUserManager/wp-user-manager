<?php
/**
 * Handles the display of the profile card shortcode generator.
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
 * WPUM_Shortcode_Profile_Card
 */
class WPUM_Shortcode_Profile_Card extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Profile card', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Profile card', 'wp-user-manager' );
		parent::__construct( 'wpum_profile_card' );
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
				'name'    => 'user_id',
				'label'   => esc_html__( 'User ID', 'wp-user-manager' ),
				'tooltip' => esc_html__( 'Leave blank to display the currently logged in user.', 'wp-user-manager' ),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'link_to_profile',
				'label'   => esc_html__( 'Link to profile?', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'display_buttons',
				'label'   => esc_html__( 'Display buttons?', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'display_cover',
				'label'   => esc_html__( 'Display profile cover?', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
		);
	}

}

new WPUM_Shortcode_Profile_Card();
