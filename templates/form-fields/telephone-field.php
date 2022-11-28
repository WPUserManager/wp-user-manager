<?php
/**
 * The template for displaying the telephone field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/telephone-field.php
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

<input
	type="tel"
	class="input-telephone"
	name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>"
	<?php
	if ( isset( $data->autocomplete ) && false === $data->autocomplete ) {
		echo ' autocomplete="off"'; }
	?>
	id="<?php echo esc_attr( $data->key ); ?>"
	placeholder="<?php echo empty( $data->placeholder ) ? '' : esc_attr( $data->placeholder ); ?>"
	value="<?php echo isset( $data->value ) ? esc_attr( $data->value ) : ''; ?>"
	<?php
	if ( ! empty( $data->required ) ) {
		echo 'required';}
	?>
	<?php
	if ( ! empty( $data->read_only ) ) {
		echo 'readonly';}
	?>
	<?php
	if ( ! empty( $data->minlength ) ) {
		echo ' minlength="' . esc_attr( $data->minlength ) . '"';}
	?>
	<?php
	if ( ! empty( $data->maxlength ) ) {
		echo ' maxlength="' . esc_attr( $data->maxlength ) . '"';}
	?>
	<?php
	if ( ! empty( $data->pattern ) ) {
		echo ' pattern="' . esc_html( $data->pattern ) . '"';}
	?>
/>
<?php
if ( ! empty( $data->description ) ) :
	?>
	<small class="description"><?php echo wp_kses_post( $data->description ); ?></small><?php endif; ?>
