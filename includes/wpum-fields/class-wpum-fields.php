<?php
/**
 * Handles loading of all field types.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registration of the Fields loader class.
 */
class WPUM_Fields {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Load files and hook into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		// Parent class template.
		require_once WPUM_PLUGIN_DIR . 'includes/abstracts/abstract-wpum-field-type.php';

		// Now load all registered field types.
		$this->load();

	}

	/**
	 * Load registered field types classes.
	 *
	 * @return void
	 */
	public function load() {

		$fields = apply_filters( 'wpum_load_fields', [
			'text',
			'email',
			'password',
			'dropdown',
			'url',
			'textarea',
			'file',
			'checkbox',
			'multicheckbox',
			'multiselect',
			'radio',
			'number',
			'datepicker',
			'telephone',
			'video',
			'audio',
			'wysiwyg',
		] );

		foreach ( $fields as $field ) {
			if ( file_exists( WPUM_PLUGIN_DIR . 'includes/wpum-fields/types/class-wpum-field-' . $field . '.php' ) ) {
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/types/class-wpum-field-' . $field . '.php';
			}

			$class = 'WPUM_Field_' . ucfirst( $field );
			if( class_exists($class) )
				( new $class )->register();
		}

	}

}

new WPUM_Fields;
