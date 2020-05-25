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

	/**
	 * Gets the value of a posted file field.
	 *
	 * @param string $key
	 * @param array  $field
	 *
	 * @return string|array
	 * @throws Exception
	 */
	public function get_posted_field( $key, $field ) {
		$file = $this->upload_file( $key, $field );
		if ( ! $file ) {
			$file = parent::get_posted_field( 'current_' . $key, $field );
		} elseif ( is_array( $file ) ) {
			$file = array_filter( array_merge( $file, (array) parent::get_posted_field( 'current_' . $key, $field ) ) );
		}
		return $file;
	}

	/**
	 * Handles the uploading of files.
	 *
	 * @param string $field_key
	 * @param array  $field
	 * @throws Exception When file upload failed
	 * @return  string|array
	 */
	protected function upload_file( $field_key, $field ) {
		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) {
			$allowed_mime_types = wpum_get_allowed_mime_types();
			if ( ! empty( $field['allowed_mime_types'] ) ) {
				$extensions         = explode( ',', $field['allowed_mime_types'] );
				$allowed_mime_types = [];
				foreach ( $extensions as $extension ) {
					$extension = strtolower( trim( str_replace( '.', '', $extension ) ) );
					foreach ( get_allowed_mime_types() as $allowed_ext => $allowed_mime_type ) {
						if ( in_array( $extension, explode( '|', $allowed_ext ) ) ) {
							$allowed_mime_types[ $allowed_ext ] = $allowed_mime_type;
							break;
						}
					}
				}
			}
			$file_urls       = array();
			$files_to_upload = wpum_prepare_uploaded_files( $_FILES[ $field_key ] );
			foreach ( $files_to_upload as $file_to_upload ) {
				// Determine max file size for the avatar field.
				$too_big_message = sprintf( esc_html__( 'The uploaded %s file is too big.', 'wp-user-manager' ), $field['label'] );
				if ( defined( 'WPUM_MAX_AVATAR_SIZE' ) && $field_key == 'user_avatar' && $file_to_upload['size'] > WPUM_MAX_AVATAR_SIZE ) {
					throw new Exception( $too_big_message );
				}
				if ( defined( 'WPUM_MAX_COVER_SIZE' ) && $field_key == 'user_cover' && $file_to_upload['size'] > WPUM_MAX_COVER_SIZE ) {
					throw new Exception( $too_big_message );
				}

				if ( isset( $field['max_file_size'] ) && ! empty( $field['max_file_size'] ) && $file_to_upload['size'] > $field['max_file_size'] ) {
					throw new Exception( $too_big_message );
				}

				$uploaded_file = wpum_upload_file( $file_to_upload, array(
					'file_key'           => $field_key,
					'allowed_mime_types' => $allowed_mime_types,
					'file_label'         => $field['label'],
				) );

				if ( is_wp_error( $uploaded_file ) ) {
					throw new Exception( $uploaded_file->get_error_message() );
				} else {
					$file_urls[] = [
						'url'  => $uploaded_file->url,
						'path' => $uploaded_file->file
					];
				}
			}
			if ( ! empty( $field['multiple'] ) ) {
				return $file_urls;
			} else {
				return current( $file_urls );
			}
		}
	}


	/**
	 * Format the output of the file field onto profile pages.
	 *
	 * @param object $field
	 * @param string $value
	 * @return string
	 */
	function get_formatted_output( $field, $value ) {
		$value = maybe_unserialize( $value );

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
		if ( 'image' === $file_type ) {
			$value = '<span class="wpum-uploaded-file-name"><img src="' . $image_src . '"></span>';
		} else if ( 'video' === $file_type && $field->get_type() === 'video' ) {
			$value = '<span class="wpum-uploaded-file-name">' . wp_video_shortcode( array( 'src' => $image_src ) ) . '</span>';
		} else if ( 'audio' === $file_type && $field->get_type() === 'audio' ) {
			$value = '<span class="wpum-uploaded-file-name">' . wp_audio_shortcode( array( 'src' => $image_src ) ) . '</span>';
		} else {
			$value = '<span class="wpum-uploaded-file-name"><code>' . $image_src . '</code></span>';
		}

		return $value;
	}
}
