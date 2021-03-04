<?php
/**
 * Registers a video file field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a text field type.
 */
class WPUM_Field_Image extends WPUM_Field_File {

	public function __construct() {
		$this->group = 'advanced';
		$this->name  = esc_html__( 'Image', 'wp-user-manager' );
		$this->type  = 'image';
		$this->template = 'file';
		$this->icon  = 'dashicons-format-image';
		$this->order = 3;
	}

	public function default_allowed_mime_types() {
		return 'jpg,jpeg,png,svg';
	}

	public function get_editor_settings() {
		return [
			'general' => [
				'default_image_size' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Default Image Size', 'wp-user-manager' ),
					'model'     => 'default_image_size',
					'hint'      => esc_html__( 'Image size, (i.e 200 x 200)', 'wp-user-manager' ),
				),
			],
			'validation' => [
				'max_file_size' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Maximum file size', 'wp-user-manager' ),
					'model'     => 'max_file_size',
					'hint'      => esc_html__( 'Enter the maximum file size users can upload through this field. The amount must be in bytes.', 'wp-user-manager' ),
				),
				'allowed_mime_types' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Allowed File Types', 'wp-user-manager' ),
					'model'     => 'allowed_mime_types',
					'hint'      => esc_html__( 'Comma Separated List of allowed file types, (i.e. jpg, gif, png, pdf)', 'wp-user-manager' ),
				),
			],
		];
	}
}
