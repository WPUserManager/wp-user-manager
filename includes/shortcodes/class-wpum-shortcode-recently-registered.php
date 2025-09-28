<?php
/**
 * Handles the display of the recently registered shortcode generator.
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
 * WPUM_Shortcode_Recently_Registered
 */
class WPUM_Shortcode_Recently_Registered extends WPUM_Shortcode_Generator {

	/**
	 * Inject the editor for this shortcode.
	 */
	public function __construct() {
		parent::__construct( 'wpum_recently_registered' );
	}

	/**
	 * Set the label and title of the shortcode.
	 *
	 * @return void
	 */
	public function set_labels() {
		$this->shortcode['title'] = esc_html__( 'Recently registered list', 'wp-user-manager' );
		$this->shortcode['label'] = esc_html__( 'Recently registered list', 'wp-user-manager' );
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
				'name'    => 'amount',
				'label'   => esc_html__( 'Amount', 'wp-user-manager' ),
				'tooltip' => esc_html__( 'How many users to display', 'wp-user-manager' ),
			),
			array(
				'type'    => 'listbox',
				'name'    => 'link_to_profile',
				'label'   => esc_html__( 'Link to profile', 'wp-user-manager' ),
				'options' => $this->get_yes_no(),
			),
		);
	}

}

new WPUM_Shortcode_Recently_Registered();
