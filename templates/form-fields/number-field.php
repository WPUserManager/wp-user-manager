<?php
/**
 * The template for displaying the number field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/number-field.php
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
	type="number"
	class="input-number"
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
	if ( ! empty( $data->min_value ) ) {
		echo ' min="' . esc_attr( $data->min_value ) . '"';}
	?>
	<?php
	if ( ! empty( $data->max_value ) ) {
		echo ' max="' . esc_attr( $data->max_value ) . '"';}
	?>
	<?php
	if ( ! empty( $data->step_size ) ) {
		echo ' step="' . esc_attr( $data->step_size ) . '"';}
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
