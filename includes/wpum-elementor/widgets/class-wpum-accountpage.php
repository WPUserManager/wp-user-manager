<?php
/**
 * Handles the display of account form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

class AccountPage extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_account_page';

	public function get_name() {
		return 'account-page';
	}

	public function get_title() {
		return esc_html__( 'Account Page', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-preferences';
	}
	
	public function get_categories() {
		return [ 'wp-user-manager' ];
	}

	public function get_keywords() {
		return [
			esc_html__( 'account', 'wp-user-manager' ),
			esc_html__( 'edit account', 'wp-user-manager' )
		];
	}

	public function render() {
		$attributes = [];
		echo call_user_func( $this->get_name(), $attributes );
	}
}