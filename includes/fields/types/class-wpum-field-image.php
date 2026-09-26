<?php
/**
 * Registers an image field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2026, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register an image field type.
 */
class WPUM_Field_Image extends WPUM_Field_File {

	/**
	 * Extensions accepted when the field has no "Allowed File Types" setting.
	 */
	const DEFAULT_ALLOWED_EXTENSIONS = 'jpg,jpeg,png,gif,webp';

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
	 * @return string
	 */
	public function default_allowed_mime_types() {
		return self::DEFAULT_ALLOWED_EXTENSIONS;
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		$settings = parent::get_editor_settings();

		$settings['validation']['allowed_mime_types']['hint'] = esc_html__( 'Comma separated list of allowed image types, (i.e. jpg, jpeg, png, gif, webp). Leave empty to allow jpg, jpeg, png, gif and webp. Only image types are accepted.', 'wp-user-manager' );

		return $settings;
	}

	/**
	 * Image uploads are limited to raster image mime types, whatever the field setting says.
	 *
	 * SVG is excluded even when a site allows it, because an SVG can carry script that runs
	 * when the uploaded file is opened directly.
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function get_allowed_upload_mime_types( $field ) {
		if ( empty( $field['allowed_mime_types'] ) ) {
			$field['allowed_mime_types'] = $this->default_allowed_mime_types();
		}

		$allowed_mime_types = array();
		foreach ( parent::get_allowed_upload_mime_types( $field ) as $extensions => $mime_type ) {
			if ( 0 === strpos( $mime_type, 'image/' ) && 'image/svg+xml' !== $mime_type ) {
				$allowed_mime_types[ $extensions ] = $mime_type;
			}
		}

		/**
		 * Filter the mime types an image field accepts.
		 *
		 * @param array $allowed_mime_types Mime types keyed by extension pattern.
		 * @param array $field              The field.
		 */
		return apply_filters( 'wpum_image_field_allowed_mime_types', $allowed_mime_types, $field );
	}

	/**
	 * Get the URL of the image stored in a field value.
	 *
	 * The value is an attachment ID when the image was set from the admin, and an uploaded
	 * file URL (or an array holding it) when it was uploaded through a WPUM form.
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	public static function get_image_url( $value ) {
		$value = wpum_maybe_unserialize( $value );

		if ( is_numeric( $value ) ) {
			$image_src = wp_get_attachment_image_src( absint( $value ), 'full' );

			return is_array( $image_src ) ? (string) $image_src[0] : '';
		}

		if ( is_array( $value ) ) {
			return isset( $value['url'] ) && is_string( $value['url'] ) ? $value['url'] : '';
		}

		return is_string( $value ) ? $value : '';
	}

	/**
	 * Format the output of the image field onto profile pages.
	 *
	 * @param object $field
	 * @param string $value
	 * @return string
	 */
	public function get_formatted_output( $field, $value ) {
		$image_src = esc_url( self::get_image_url( $value ) );

		if ( empty( $image_src ) ) {
			return '';
		}

		$alt = is_object( $field ) && method_exists( $field, 'get_name' ) ? $field->get_name() : '';

		return '<span class="wpum-uploaded-image-name"><img src="' . $image_src . '" alt="' . esc_attr( $alt ) . '"></span>';
	}
}
