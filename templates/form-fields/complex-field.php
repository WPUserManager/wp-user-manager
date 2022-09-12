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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$parent = new WPUM_Field( $data->id );

if ( ! $parent ) {
	return;
}

$fields = WPUM()->fields->get_fields(array(
	'group_id' => $parent->get_group_id(),
	'parent'   => $parent->get_ID(),
	'order'    => 'ASC',
));

$parent_key = $parent->get_key();

echo sprintf(
	'<fieldset class="fieldset-%s"><legend>%s %s</legend>',
	esc_attr( $parent_key ),
	esc_html( $data->label ),
	( isset( $data->required ) && $data->required ? '<span class="wpum-required">*</span>' : '' )
);

$button_label = $parent->get_meta( 'button_label' );
$max_rows     = $parent->get_meta( 'max_rows' );

if ( count( $fields ) ) {

	$field_keys = array_map( function( $field ) {
			return $field->get_key();
	}, $fields );

	$value = isset( $data->value ) ? $data->value : array();

	$values = array_map( function ( $value ) {
		if ( isset( $value['_type'] ) ) {
			unset( $value['_type'] );
		}

		return $value;
	}, (array) $value );

	$index = 0;
	if ( empty( $values ) ) {
		$values[] = true;
	}
	$values[] = true;

	$clone_row_index = count( $values ) - 1;

	do {
		$clone_row = (int) $index === (int) $clone_row_index;
		echo '<div class="fieldset-wpum_field_group' . ( $clone_row ? ' fieldset-wpum_field_group-clone' : '' ) . '">';

		echo sprintf(
			'<a href="#" class="remove-repeater-row" title="%s">x</a>',
			esc_html__( 'Remove', 'wp-user-manager' )
		);

		foreach ( $fields as $field ) {

			$options        = array();
			$options_needed = ! $field->is_primary() && in_array( $field->get_type(), array( 'dropdown', 'multiselect', 'radio', 'multicheckbox' ), true );
			if ( $options_needed ) {

				$stored_options = $field->get_meta( 'dropdown_options' );
				if ( ! empty( $stored_options ) && is_array( $stored_options ) ) {
					foreach ( $stored_options as $option ) {
						$options[ $option['value'] ] = $option['label'];
					}
				}
			}


			$key   = $field->get_key();
			$value = isset( $values[ $index ][ $key ] ) ? $values[ $index ][ $key ] : '';

			if ( $index > 0 && ! $clone_row ) {
				if ( isset( $values[ $index ][ $key ] ) ) {
					$value = $values[ $index ][ $key ];
				}
			}

			$field = array(
				'id'            => $field->get_ID(),
				'label'         => $field->get_name(),
				'type'          => $field->get_type(),
				'required'      => $field->get_meta( 'required' ),
				'placeholder'   => $field->get_meta( 'placeholder' ),
				'description'   => $field->get_description(),
				'priority'      => $field->get_key(),
				'primary_id'    => $field->get_primary_id(),
				'options'       => $options,
				'template'      => $field->get_parent_type(),
				'name'          => "{$parent_key}[{$index}][{$key}]",
				'value'         => $value,
				'max_file_size' => $field->get_meta( 'max_file_size' ),
			);

			WPUM()->templates
				->set_template_data( array(
					'field' => $field,
					'key'   => $key,
				) )
				->get_template_part( 'forms/form-registration-fields', 'field' );
		}

		echo '</div>';

		$index++;

	} while ( isset( $values[ $index ] ) );
}

$max_rows     = ! empty( $max_rows ) ? intval( $max_rows ) : 0;
$button_label = ! empty( $button_label ) ? $button_label : __( 'Add row', 'wp-user-manager' );

echo sprintf(
	'<button type="button" class="add-repeater-row" data-max-row="%d">%s</button>',
	esc_attr( $max_rows ),
	esc_html( $button_label )
);

echo '</fieldset>';
