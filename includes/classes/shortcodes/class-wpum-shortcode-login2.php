<?php
/**
 * Handles the display of login form generator.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Shortcode_Login2 extends WPUM_Shortcode_Generator {

	public function __construct() {
		$this->shortcode['title'] = esc_html__( 'Login2' );
		$this->shortcode['label'] = esc_html__( 'Login2' );
		parent::__construct( 'wpum_login2' );
	}

	public function define_fields() {
		return array(
			array(
				'type' => 'container',
				'html' => sprintf( '<p class="no-margin">%s</p>', esc_html__( 'Login Redirect URL (optional):' ) ),
			),
			array(
				'type'     => 'textbox',
				'name'     => 'login-redirect',
				'minWidth' => 320,
				'tooltip'  => esc_attr__( 'Enter an URL here to redirect to after login.' ),
			),
            array(
                'type' => 'container',
                'html' => sprintf( '<p class="no-margin">%s</p>', esc_html__( 'Logout Redirect URL (optional):' ) ),
            ),
            array(
                'type'     => 'textbox',
                'name'     => 'logout-redirect',
                'minWidth' => 320,
                'tooltip'  => esc_attr__( 'Enter an URL here to redirect to after logout.' ),
            ),
		);
	}

}

new WPUM_Shortcode_Login2;
