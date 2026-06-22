<?php
/**
 * Registers a image field for the forms.
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
class WPUM_Field_Image extends WPUM_Field_File {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->group    = 'advanced';
		$this->type     = 'image';
		$this->template = 'image';
		$this->icon     = 'dashicons-format-image';
		$this->order    = 3;
	}

	/**
	 * Set the name of the field.
	 *
	 * @return void
	 */
	public function set_name() {
		$this->name = esc_html__( 'Image', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['validation'] ) );
	}

	/**
	 * @return string
	 */
	public function default_allowed_mime_types() {
		return 'jpg,jpeg,png,gif,webp';
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return array(
			'validation' => array(
				'max_file_size'      => array(
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
					'hint'      => esc_html__( 'Comma Separated List of allowed file types, (i.e. jpg, jpeg, gif, png, pdf)', 'wp-user-manager' ),
				),
			),
		);
	}

	/**
	 * Format the output of the file field onto profile pages.
	 *
	 * @param object $field
	 * @param string $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		$value = wpum_maybe_unserialize( $value );

		if ( is_numeric( $value ) ) {
			$image_src = wp_get_attachment_image_src( absint( $value ) );
			$image_src = $image_src ? $image_src[0] : '';
		} elseif ( is_array( $value ) && isset( $value['url'] ) ) {
			$image_src = $value['url'];
		} else {
			$image_src = $value;
		}

		$extension = substr( strrchr( $image_src, '.' ), 1 );
		$file_type = wp_ext2type( $extension );
		$value     = '<span class="wpum-uploaded-image-name"><img src="' . $image_src . '"></span>';

		return $value;
	}
}
