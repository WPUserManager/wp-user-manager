<?php
/**
 * Registers a video file field for the forms.
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
class WPUM_Field_Image extends WPUM_Field_File {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->group             = 'advanced';
		$this->name              = esc_html__( 'Image', 'wp-user-manager' );
		$this->type              = 'image';
		$this->template          = 'file';
		$this->icon              = 'dashicons-format-image';
		$this->order             = 4;
		$this->min_addon_version = '2.1';
	}

	/**
	 * @return string
	 */
	public function default_allowed_mime_types() {
		return 'jpg,jpeg,png,gif,webp,svg,avif,ico,apng';
	}
}
