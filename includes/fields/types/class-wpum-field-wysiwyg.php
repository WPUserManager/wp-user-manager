<?php
/**
 * Registers a WYSIWYG field for the forms.
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
 * Register a WYSIWYG field type.
 */
class WPUM_Field_Wysiwyg extends WPUM_Field_Textarea {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name              = esc_html__( 'WYSIWYG', 'wp-user-manager' );
		$this->type              = 'wysiwyg';
		$this->group             = 'advanced';
		$this->icon              = 'dashicons-align-left';
		$this->order             = 3;
		$this->template          = 'wysiwyg';
		$this->min_addon_version = '2.1';
	}
}
