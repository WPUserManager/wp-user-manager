<?php
/**
 * The template for displaying the user role field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/userrole-field.php
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_type = empty( $data->allow_multiple ) ? 'select' : 'multiselect';

$data->options = apply_filters( 'wpum_user_role_field_options', $data->options, $data );

WPUM()->templates->set_template_data( $data )->get_template_part( 'form-fields/' . $field_type, 'field' );
