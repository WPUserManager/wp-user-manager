<?php
/**
 * The template for displaying the checkbox field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/checkbox-field.php
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

<input type="checkbox" class="input-checkbox" name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>" id="<?php echo esc_attr( $data->key ); ?>" <?php checked( ! empty( $data->value ), true ); ?> value="1"
															   <?php
																if ( ! empty( $data->required ) ) {
																	echo 'required';}
																?>
 />
<?php
if ( ! empty( $data->description ) ) :
	?>
	<small class="description"><?php echo wp_kses_post( $data->description ); ?></small><?php endif; ?>
