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
		] );

		$paths = apply_filters( 'wpum_load_fields_file_paths', array( WPUM_PLUGIN_DIR . 'includes/wpum-fields/types/' ) );

		foreach ( $fields as $field ) {
			foreach ( $paths as $path ) {
				if ( file_exists( $path . 'class-wpum-field-' . $field . '.php' ) ) {
					require_once $path . '/class-wpum-field-' . $field . '.php';
					break;
				}
			}
		}
	}

}
