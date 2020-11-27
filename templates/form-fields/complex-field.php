<?php
/**
 * The template for displaying the repeater field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/complex-field.php
 *
 * HOWEVER, on occasion WPUM will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0.0
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


$parent = new WPUM_Field( $data->id );

if( !$parent ){
	return;
}

$fields 	= WPUM()->fields->get_fields([
	'group_id' => $parent->get_group_id(),
	'parent'   => $parent->get_ID()
]);

$parent_key = $parent->get_key();

if( isset( $data->value ) && is_array( $data->value ) ){

	$field_keys = array_map( function( $field ){
		return $field->get_key();
	}, $fields );

	if( count( array_diff( array_keys( $data->value ), $field_keys ) ) > 0 ){

		foreach( $data->value as $index => $value ){

			$field		  = $data;
			$field->value = $value;
			$field->index = $index;

			WPUM()->templates
				->set_template_data( [ 'field' => (array) $field, 'key' => $field->key ] )
				->get_template_part( 'forms/form-registration-fields', 'field' );

		}
		return;
	}

}


if( !isset( $data->value ) ){
	echo sprintf( '<fieldset class="fieldset-%s"><label>%s %s</label>', esc_attr( $parent_key ), esc_html( $data->label ), ( isset( $data->required ) && $data->required ? '<span class="wpum-required">*</span>' : '' ) );
}

$index 		  = isset( $data->index ) ? $data->index : 0;
$button_label = $parent->get_meta( 'button_label' );
echo sprintf( '<button type="button" class="add-repeater-row">%s</button>', !empty( $button_label ) ? $button_label : esc_html__( 'Add row', 'wp-user-manager' ) );

foreach( $fields as $field ){

	$options 		= [];
	$options_needed = ! $field->is_primary() && in_array( $field->get_type(), [ 'dropdown', 'multiselect', 'radio', 'multicheckbox' ] );
	if ( $options_needed ) {

		$stored_options = $field->get_meta( 'dropdown_options' );
		if ( ! empty( $stored_options ) && is_array( $stored_options ) ) {
			foreach ( $stored_options as $option ) {
				$options[ $option['value'] ] = $option['label'];
			}
		}
	}

	$key   = $field->get_key();
	$value = isset( $data->value[ $key ] ) ? $data->value[ $key ] : '';
	$field = array(
		'id'		  => $field->get_ID(),
		'label'       => $field->get_name(),
		'type'        => $field->get_type(),
		'required'    => $field->get_meta( 'required' ),
		'placeholder' => $field->get_meta( 'placeholder' ),
		'description' => $field->get_description(),
		'priority'    => $field->get_key(),
		'primary_id'  => $field->get_primary_id(),
		'options'     => $options,
		'template'    => $field->get_parent_type(),
		'name'		  => "{$parent_key}[{$index}][{$key}]",
		'value'		  => $value
	);

	WPUM()->templates
		->set_template_data( [ 'field' => $field, 'key' => $key ] )
		->get_template_part( 'forms/form-registration-fields', 'field' );
}

if( !isset( $data->value ) ){
	echo '</fieldset>';
}

?>
