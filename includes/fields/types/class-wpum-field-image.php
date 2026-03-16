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
class WPUM_Field_Image extends WPUM_Field_Type {

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
	 * Gets the value of a posted image field.
	 *
	 * @param string $key
	 * @param array  $field
	 *
	 * @return string|array
	 * @throws Exception
	 */
	public function get_posted_field( $key, $field ) {
		$file = $this->upload_image( $key, $field );

		if ( ! $file ) {
			$file = parent::get_posted_field( 'current_' . $key, $field );
		} elseif ( is_array( $file ) ) {
			$file = array_filter( array_merge( $file, (array) parent::get_posted_field( 'current_' . $key, $field ) ) );
		}

		return $file;
	}

	/**
	 * Handles the uploading of images.
	 *
	 * @param string $field_key
	 * @param array  $field
	 * @throws Exception When image upload failed
	 * @return  string|array
	 */
	protected function upload_image( $field_key, $field ) {
		if ( ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in parent form handler.
			$allowed_mime_types = wpum_get_allowed_mime_types();

			if ( ! empty( $field['allowed_mime_types'] ) ) {
				$extensions         = explode( ',', $field['allowed_mime_types'] );
				$allowed_mime_types = array();
				foreach ( $extensions as $extension ) {
					$extension = strtolower( trim( str_replace( '.', '', $extension ) ) );
					foreach ( get_allowed_mime_types() as $allowed_ext => $allowed_mime_type ) {
						if ( in_array( $extension, explode( '|', $allowed_ext ), true ) ) {
							$allowed_mime_types[ $allowed_ext ] = $allowed_mime_type;
							break;
						}
					}
				}
			}

			$file_to_upload = reset( wpum_prepare_uploaded_files( $_FILES[ $field_key ] ) ); // phpcs:ignore

			// Determine max file size for the image field.
			// translators: %s field label
			$too_big_message = sprintf( esc_html__( 'The uploaded %s image is too big.', 'wp-user-manager' ), $field['label'] );

			if ( isset( $field['max_file_size'] ) && ! empty( $field['max_file_size'] ) && $file_to_upload['size'] > $field['max_file_size'] ) {
				throw new Exception( esc_html( $too_big_message ) );
			}

			$uploaded_file = wpum_upload_file( $file_to_upload, array(
				'file_key'           => $field_key,
				'allowed_mime_types' => $allowed_mime_types,
				'file_label'         => $field['label'],
			) );

			if ( is_wp_error( $uploaded_file ) ) {
				throw new Exception( esc_html( $uploaded_file->get_error_message() ) );
			} else {
				$file_url = array(
					'url'  => $uploaded_file->url,
					'path' => $uploaded_file->file,
				);
			}
			
			return $file_url;
		}

		return '';
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
