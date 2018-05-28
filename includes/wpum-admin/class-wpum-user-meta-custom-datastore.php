<?php
/**
 * Custom datastore for carbon fields.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

use Carbon_Fields\Field\Field;
use Carbon_Fields\Datastore\Datastore;
use Carbon_Fields\Helper\Helper;

/**
 * Stores serialized values in the database
 */
class WPUM_User_Meta_Custom_Datastore extends Datastore {

	/**
	 * Initialization tasks for concrete datastores.
	 **/
	public function init() {

	}

	protected function get_key_for_field( Field $field ) {
		$key = $field->get_base_name();
		return $key;
	}

	/**
	 * Save a single key-value pair to the database with autoload
	 *
	 * @param string $key
	 * @param string $value
	 * @param bool $autoload
	 */
	protected function save_key_value_pair_with_autoload( $key, $value, $autoload ) {
		update_user_meta( $this->object_id, $key, $value );
	}

	/**
	 * Load the field value(s)
	 *
	 * @param Field $field The field to load value(s) in.
	 * @return array
	 */
	public function load( Field $field ) {
		$key   = $this->get_key_for_field( $field );
		$value = get_user_meta( $this->object_id, $key, true );
		return $value;
	}

	/**
	 * Save the field value(s)
	 *
	 * @param Field $field The field to save.
	 */
	public function save( Field $field ) {
		if ( ! empty( $field->get_hierarchy() ) ) {
			return; // only applicable to root fields
		}
		$key   = $this->get_key_for_field( $field );
		$value = $field->get_value();
		if ( is_a( $field, '\\Carbon_Fields\\Field\\Complex_Field' ) ) {
			$value = $field->get_value_tree();
		}
		$this->save_key_value_pair_with_autoload( $key, $value, $field->get_autoload() );
	}

	/**
	 * Delete the field value(s)
	 *
	 * @param Field $field The field to delete.
	 */
	public function delete( Field $field ) {
		if ( ! empty( $field->get_hierarchy() ) ) {
			return; // only applicable to root fields
		}
		$key = $this->get_key_for_field( $field );
		delete_user_meta( $this->get_object_id(), $key );
	}
}
