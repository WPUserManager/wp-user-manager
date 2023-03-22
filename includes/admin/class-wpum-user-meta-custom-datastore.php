<?php
/**
 * Custom datastore for carbon fields.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

use WPUM\Carbon_Fields\Field\Field;
use WPUM\Carbon_Fields\Datastore\Datastore;
use WPUM\Carbon_Fields\Helper\Helper;

/**
 * Stores serialized values in the database
 */
class WPUM_User_Meta_Custom_Datastore extends Datastore {

	/**
	 * Initialization tasks for concrete datastores.
	 **/
	public function init() {

	}

	/**
	 * @param Field $field
	 *
	 * @return string
	 */
	protected function get_key_for_field( Field $field ) {
		return $field->get_base_name();
	}

	/**
	 * Save a single key-value pair to the database with autoload
	 *
	 * @param string $key
	 * @param string $value
	 * @param bool   $autoload
	 */
	protected function save_key_value_pair_with_autoload( $key, $value, $autoload ) {
		$value = apply_filters( 'wpum_custom_field_admin_meta_update', $value, $key, $this->object_id, $value );

		update_user_meta( $this->object_id, $key, $value );
	}

	/**
	 * Load the field value(s)
	 *
	 * @param Field $field The field to load value(s) in.
	 *
	 * @return mixed
	 */
	public function load( Field $field ) {
		$key   = $this->get_key_for_field( $field );
		$value = get_user_meta( $this->object_id, $key, true );
		if ( empty( $value ) ) {
			$value = '';
		}
		if ( empty( $value ) && is_a( $field, '\\WPUM\\Carbon_Fields\\Field\\Complex_Field' ) ) {
			$value = array();
		}

		$id         = str_replace( 'wpum_field_', '', $field->get_base_name() );
		$wpum_field = new WPUM_Field( $id );

		return apply_filters( 'wpum_custom_field_value', $value, $wpum_field, $this->object_id );
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
		if ( is_a( $field, '\\WPUM\\Carbon_Fields\\Field\\Complex_Field' ) ) {
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
