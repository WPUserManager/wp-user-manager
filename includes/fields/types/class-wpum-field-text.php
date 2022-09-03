<?php
/**
 * Registers a text field for the forms.
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
class WPUM_Field_Text extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name          = esc_html__( 'Single Line Text', 'wp-user-manager' );
		$this->type          = 'text';
		$this->icon          = 'dashicons-editor-textcolor';
		$this->order         = 3;
		$this->allow_default = true;
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
				'pattern'   => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Pattern', 'wp-user-manager' ),
					'model'     => 'pattern',
					'hint'      => esc_html__( 'A regular expression to validate number formats.', 'wp-user-manager' ) . ' <a target="_blank" href="http://html5pattern.com/">' . esc_html__( 'Example patterns' ) . '</a>',
				),
			),
		);
	}

}

