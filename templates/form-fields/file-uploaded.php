<?php
/**
 * The template for displaying the uploaded content for a file field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/file-uploaded.php
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

?>

<div class="wpum-uploaded-file">
	<?php
	if ( is_numeric( $data->value ) ) {
		$image_src = wp_get_attachment_image_src( absint( $data->value ) );
		$image_src = $image_src ? $image_src[0] : '';
	} else {
		$image_src = $data->value;
	}
	$extension = ! empty( $data->extension ) ? $data->extension : substr( strrchr( $image_src, '.' ), 1 );
	if ( 'image' === wp_ext2type( $extension ) ) :
		?>
		<span class="wpum-uploaded-file-preview"><img src="<?php echo esc_url( $image_src ); ?>" /> <a class="wpum-remove-uploaded-file" href="#">[<?php esc_html_e( 'remove', 'wp-user-manager' ); ?>]</a></span>
	<?php elseif ( 'video' === wp_ext2type( $extension ) && 'video' === $data->type ) : ?>
		<span class="wpum-uploaded-file-preview"><?php echo wp_video_shortcode( array( 'src' => $image_src ) ); // phpcs:ignore ?> <a class="wpum-remove-uploaded-file" href="#">[<?php esc_html_e( 'remove', 'wp-user-manager' ); ?>]</a></span>
	<?php elseif ( 'audio' === wp_ext2type( $extension ) && 'audio' === $data->type ) : ?>
		<span class="wpum-uploaded-file-preview"><?php echo wp_audio_shortcode( array( 'src' => $image_src ) ); // phpcs:ignore ?> <a class="wpum-remove-uploaded-file" href="#">[<?php esc_html_e( 'remove', 'wp-user-manager' ); ?>]</a></span>
	<?php else : ?>
		<span class="wpum-uploaded-file-name"><code><?php echo esc_html( basename( $image_src ) ); ?></code> <a class="wpum-remove-uploaded-file" href="#">[<?php esc_html_e( 'remove', 'wp-user-manager' ); ?>]</a></span>
	<?php endif; ?>

	<input type="hidden" class="input-text" name="<?php echo esc_attr( $data->name ); ?>" value="<?php echo esc_attr( $data->value ); ?>" />
</div>
