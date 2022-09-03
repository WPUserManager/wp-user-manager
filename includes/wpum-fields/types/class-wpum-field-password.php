<?php
/**
 * Registers a password field for the forms.
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
 * Register a text field type.
 */
class WPUM_Field_Password extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name  = esc_html__( 'Password', 'wp-user-manager' );
		$this->type  = 'password';
		$this->icon  = 'dashicons-admin-network';
		$this->order = 3;
	}

	/**
	 * @return array
	 */
	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['validation'] ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return array(
			'validation' => array(
				'maxlength' => array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Character Limit', 'wp-user-manager' ),
					'model'     => 'maxlength',
					'hint'      => esc_html__( 'Leave blank for no limit.', 'wp-user-manager' ),
				),
			),
		);
	}

}
