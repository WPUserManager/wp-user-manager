<?php
/**
 * Registers a hidden field for the forms.
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
 * Register a hidden field type.
 */
class WPUM_Field_Hidden extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->name              = esc_html__( 'Hidden', 'wp-user-manager' );
		$this->type              = 'hidden';
		$this->icon              = 'dashicons-hidden';
		$this->group             = 'advanced';
		$this->allow_default     = true;
		$this->min_addon_version = '2.2.1';
	}
}

