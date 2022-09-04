<?php
/**
 * The template for displaying the radio field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/radio-field.php
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

$data->default = empty( $data->default ) ? current( array_keys( $data->options ) ) : $data->default;
$default       = ! empty( $data->value ) ? $data->value : $data->default;

foreach ( $data->options as $option_key => $value ) : ?>

	<label><input type="radio" name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>" value="<?php echo esc_attr( $option_key ); ?>" <?php checked( $default, $option_key ); ?> /> <?php echo esc_html( $value ); ?></label>

<?php endforeach; ?>

<?php
if ( ! empty( $data->description ) ) :
	?>
	<small class="description"><?php echo wp_kses_post( $data->description ); ?></small><?php endif; ?>
