<?php
/**
 * Registers a multi select dropdown field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a dropdown field type.
 */
class WPUM_Field_Multiselect extends WPUM_Field_Type {

	public function __construct() {
		$this->name  = esc_html__( 'Multi Select', 'wp-user-manager' );
		$this->type  = 'multiselect';
		$this->icon  = 'dashicons-editor-alignleft';
		$this->order = 3;
	}

}
