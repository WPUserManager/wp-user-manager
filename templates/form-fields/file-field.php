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
 *
 * @version 1.0.0
 */

 // Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$classes            = array( 'input-text' );
$allowed_mime_types = ! empty( $data->allowed_mime_types ) ? explode( ',', $data->allowed_mime_types ) : array_values( get_allowed_mime_types() );
$field_name         = isset( $data->name ) ? $data->name : $data->key;
$field_name         .= ! empty( $data->multiple ) ? '[]' : '';
$file_size = isset( $data->max_file_size ) ? $data->max_file_size : false;

if ( ! empty( $data->ajax ) && wpum_user_can_upload_file_via_ajax() ) {
	wp_enqueue_script( 'wpum-ajax-file-upload' );
	$classes[] = 'wpum-file-upload';
}
?>

<div class="wpum-uploaded-files">
  <?php if ( ! empty( $data->value ) ) :
	  if ( is_array( $data->value ) ) :
		  if ( isset( $data->value['url'] ) ) :
			  WPUM()->templates->set_template_data( [
					  'key'   => $data->key,
					  'name'  => 'current_' . $field_name,
					  'value' => $data->value['url'],
					  'type'  => $data->type,
					  'field' => [],
				  ] )->get_template_part( 'form-fields/file', 'uploaded' );
		  endif;
	  elseif ( $value = $data->value ) :
		  WPUM()->templates->set_template_data( [
				  'key'   => $data->key,
				  'name'  => 'current_' . $field_name,
				  'value' => $value,
				  'type'  => $data->type,
				  'field' => [],
			  ] )->get_template_part( 'form-fields/file', 'uploaded' );
	  endif;
  endif; ?>
</div>
<?php
$default_image_size = isset( $data->default_image_size ) ? explode( $data->default_image_size, 'x' ) : $data->key == 'user_avatar' ? [250, 250] : [];
$default_image_size = apply_filters('default_image_size', $default_image_size );
?>
<input type="file" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-file_types="<?php echo esc_attr( implode( '|', $allowed_mime_types ) ); ?>" <?php if ( ! empty( $data->multiple ) ) echo 'multiple'; ?> name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?><?php if ( ! empty( $data->multiple ) ) echo '[]'; ?>" id="<?php echo esc_attr( $data->key ); ?>" placeholder="<?php echo empty( $data->placeholder ) ? '' : esc_attr( $data->placeholder ); ?>" data-file-key="<?php echo esc_attr( $data->key ); ?>" data-file-nonce="<?php echo wp_create_nonce( 'verify_account_form' ); ?>" data-file-width="<?php echo isset( $default_image_size[0] ) ? trim( $default_image_size[0] ) : ''; ?>" data-file-height="<?php echo isset( $default_image_size[1] ) ? trim( $default_image_size[1] ) : ''; ?>" data-file-poster="<?php echo isset( $data->value ) ? $data->value : ''; ?>" data-input-type="<?php echo esc_attr( $data->type ); ?>" />
<small class="description">
<?php if ( ! empty( $data->description ) ) :
		 echo $data->description;
	 	endif;
	  printf( __( 'Maximum file size: %s.', 'wp-user-manager' ), wpum_max_upload_size( isset( $data->key ) ? $data->key : '', $file_size ) ); ?>
</small>
