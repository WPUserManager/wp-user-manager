<?php
/**
 * Handles the display of account form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Account page widget
 */
class WPUM_AccountPage extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_account_page';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-preferences';

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Account Page', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'account', 'wp-user-manager' ),
			esc_html__( 'edit account', 'wp-user-manager' ),
		);
	}
}
