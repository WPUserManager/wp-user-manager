<?php
/**
 * The template for displaying the image field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/image-field.php
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

wp_enqueue_style( 'wpum-filepond' );
wp_enqueue_script( 'wpum-filepond-init' );

$classes            = array( 'wpum-image-field' );
$field_name         = isset( $data->name ) ? $data->name : $data->key;
$allowed_mime_types = ( new WPUM_Field_Image() )->get_allowed_upload_mime_types( array( 'allowed_mime_types' => isset( $data->allowed_mime_types ) ? $data->allowed_mime_types : '' ) );
$max_file_size      = ! empty( $data->max_file_size ) ? absint( $data->max_file_size ) : wp_max_upload_size();
$current_image      = WPUM_Field_Image::get_image_url( isset( $data->value ) ? $data->value : '' );
?>
<div class="wpum-uploaded-image">
	<?php if ( ! empty( $current_image ) ) : ?>
		<input type="hidden" class="input-text" name="<?php echo esc_attr( 'current_' . $field_name ); ?>" value="<?php echo esc_url( $current_image ); ?>" />
	<?php endif; ?>
</div>

<input type="file" id="<?php echo esc_attr( $data->key ); ?>" name="<?php echo esc_attr( $field_name ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" accept="<?php echo esc_attr( implode( ',', array_unique( array_values( $allowed_mime_types ) ) ) ); ?>" data-file_types="<?php echo esc_attr( implode( '|', array_unique( array_values( $allowed_mime_types ) ) ) ); ?>" data-file_size="<?php echo esc_attr( $max_file_size ); ?>" />
<small class="description">
<?php
if ( ! empty( $data->description ) ) :
	echo esc_html( $data->description );
endif;
echo ' ';
// translators: %s Maximum file size
printf( esc_html__( 'Maximum file size: %s.', 'wp-user-manager' ), esc_html( size_format( $max_file_size ) ) );
?>
</small>
