<?php
/**
 * Registers a telephone field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a text field type.
 */
class WPUM_Field_Telephone extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name              = esc_html__( 'Telephone', 'wp-user-manager' );
		$this->type              = 'telephone';
		$this->icon              = 'dashicons-phone';
		$this->order             = 3;
		$this->min_addon_version = '2.1';
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
				'pattern'   => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Pattern', 'wp-user-manager' ),
					'model'     => 'pattern',
					'hint'      => esc_html__( 'A regular expression to validate telephone number formats.', 'wp-user-manager' ) . ' <a target="_blank" href="http://html5pattern.com/Phones">' . esc_html__( 'Example patterns' ) . '</a>',
				),
				'minlength' => array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Minimum Value', 'wp-user-manager' ),
					'model'     => 'minlength',
				),
				'maxlength' => array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Maximum Value', 'wp-user-manager' ),
					'model'     => 'maxlength',
				),
			),
		);
	}

}
