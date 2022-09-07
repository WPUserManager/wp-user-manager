<?php
/**
 * The template for displaying the hidden field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/hidden-field.php
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
	type="hidden"
	name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>"
	<?php
	if ( isset( $data->autocomplete ) && false === $data->autocomplete ) {
		echo ' autocomplete="off"'; }
	?>
	id="<?php echo esc_attr( $data->key ); ?>"
	value="<?php echo isset( $data->value ) ? esc_attr( $data->value ) : ''; ?>"
/>
