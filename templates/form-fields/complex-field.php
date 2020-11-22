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

$fields = WPUM()->fields->get_fields([
	'group_id' => $parent->get_group_id(),
	'parent'   => $parent->get_ID()
]);


foreach( $fields as $field ){

	$key   = !empty( $field->get_primary_id() ) ? str_replace( ' ', '_', strtolower( $field->get_primary_id() ) ) : $field->get_meta( 'user_meta_key' );
	$field = array(
		'id'		  => $field->get_ID(),
		'label'       => $field->get_name(),
		'type'        => $field->get_type(),
		'required'    => $field->get_meta( 'required' ),
		'placeholder' => $field->get_meta( 'placeholder' ),
		'description' => $field->get_description(),
		'priority'    => $key,
		'primary_id'  => $field->get_primary_id(),
		'options'     => array(),
		'template'    => $field->get_parent_type(),
	);

	WPUM()->templates
		->set_template_data( [ 'field' => $field, 'key' => $key ] )
		->get_template_part( 'forms/form-registration-fields', 'field' );
}

?>
