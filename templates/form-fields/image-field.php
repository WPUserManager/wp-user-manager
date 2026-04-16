<?php
/**
 * The template for displaying the file field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/file-field.php
 *
 * HOWEVER, on occasion WPUM will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$classes            = array( 'wpum-image-field' );
$allowed_mime_types = ! empty( $data->allowed_mime_types ) ? explode( ',', $data->allowed_mime_types ) : array_values( get_allowed_mime_types() );
$field_name         = isset( $data->name ) ? $data->name : $data->key;
$file_size          = isset( $data->max_file_size ) ? $data->max_file_size : false;
$max_file_size      = wpum_max_upload_size( isset( $data->key ) ? $data->key : '', $file_size );
$current_image      = '';

if ( is_numeric( $data->value ) ) {
	$current_image = wp_get_attachment_image_src( absint( $data->value ) )[0] ?? '';
} elseif ( is_array( $data->value ) ) {
	$current_image = $data->value['url'] ?? '';
} else {
	$current_image = $data->value ?: '';
}
?>
<div class="wpum-uploaded-image">
	<?php if ( ! empty( $current_image ) ): ?>
		<input type="hidden" class="input-text" name="<?php echo esc_attr( 'current_' . $field_name ); ?>" value="<?php echo esc_attr( $current_image ); ?>" />
	<?php endif; ?>
</div>

<input type="file" placeholder="<?php echo empty( $data->placeholder ) ? '' : esc_attr( $data->placeholder ); ?>" id="<?php echo esc_attr( $data->key ); ?>" name="<?php echo esc_attr( $field_name ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-file_types="<?php echo esc_attr( implode( '|', $allowed_mime_types ) ); ?>" data-file_size="<?php echo esc_attr( str_replace( ' ', '', $max_file_size ) ); ?>" />
<small class="description">
<?php
if ( ! empty( $data->description ) ) :
	echo esc_html( $data->description );
endif;
?>
</small>
