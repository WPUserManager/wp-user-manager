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
class WPUM_Field_Video extends WPUM_Field_File {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->group             = 'advanced';
		$this->name              = esc_html__( 'Video', 'wp-user-manager' );
		$this->type              = 'video';
		$this->template          = 'file';
		$this->icon              = 'dashicons-video-alt2';
		$this->order             = 3;
		$this->min_addon_version = '2.1';
	}

	/**
	 * @return string
	 */
	public function default_allowed_mime_types() {
		return 'mp4,m4v,mov,wmv,avi,mpg';
	}

}
