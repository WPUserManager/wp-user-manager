<?php
/**
 * Registers a States field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2021, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a dropdown field type.
 */
class WPUM_Field_States extends WPUM_Field_Type {

	public function __construct() {
		$this->name  = esc_html__( 'US States', 'wp-user-manager' );
		$this->type  = 'states';
		$this->icon  = 'dashicons-location-alt';
		$this->group = 'advanced';
		$this->label = 'State';
		$this->allow_default = false;
		$this->min_addon_version = '2.3';
	}

	public function get_data_keys() {
		$keys = parent::get_data_keys();

		return array_merge( $keys, array_keys( $this->get_editor_settings()['general'] ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return [
			'general' => [
				'allow_multiple' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Allow multiple selection', 'wp-user-manager' ),
					'model'   => 'allow_multiple',
					'default' => false,
				)
			],
		];
	}

	/**
	 * Format the output onto the profiles for the taxonomy field.
	 *
	 * @param object $field
	 * @param mixed $value
	 * @return string
	 */
	function get_formatted_output( $field, $value ) {
		if ( ! is_array( $value ) ) {
			$value = array( $value );
		}

		return implode( ', ', wp_list_pluck( $value ) );
	}

}
