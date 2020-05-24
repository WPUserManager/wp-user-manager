<?php
/**
 * Registers a file field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a text field type.
 */
class WPUM_Field_File extends WPUM_Field_Type {

	public function __construct() {
		$this->group = 'advanced';
		$this->name  = esc_html__( 'File', 'wp-user-manager' );
		$this->type  = 'file';
		$this->icon  = 'dashicons-paperclip';
		$this->order = 3;
	}

	public function get_data_keys() {
		return array_keys( $this->get_editor_settings()['validation'] );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return [
			'validation' => [
				'max_file_size' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Maximum file size', 'wp-user-manager' ),
					'model'     => 'max_file_size',
					'hint'      => esc_html__( 'Enter the maximum file size users can upload through this field. The amount must be in bytes.', 'wp-user-manager' ),
				),
			],
		];
	}

}
