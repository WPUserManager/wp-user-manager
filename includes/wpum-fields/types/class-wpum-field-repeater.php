<?php
/**
 * Registers a repeater field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a text field type.
 */
class WPUM_Field_Repeater extends WPUM_Field_Type {

	public function __construct() {
		$this->group = 'advanced';
		$this->name  = esc_html__( 'Repeater', 'wp-user-manager' );
		$this->type  = 'repeater';
		$this->template = 'complex';
		$this->icon  = 'dashicons-menu-alt';
		$this->order = 1;

		add_filter( 'wpum_fields_editor_deregister_model', array( $this, 'parent_field_model_data' ), 10, 2 );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {

		// $fields = WPUM()->fields->get_fields(
		// 	[
		// 		'orderby'  => 'field_order',
		// 		'order'    => 'ASC',
		// 		'parent'   => 0
		// 	]
		// );

		// error_log(json_encode($fields));


		return [
			'fields' => [
				'repeater' => array(
					'type'      => 'repeater',
					'model'     => 'repeater',
					'fields' 	=> array()
				)
			],
		];
	}

	/**
	 * @return array
	 */
	public function parent_field_model_data( $model, $primary_field_id ){

		if( isset( $model['repeater'] ) ){
			$model['parent'] = intval( $_POST['field_id'] );
		}
		return $model;
	}
}
