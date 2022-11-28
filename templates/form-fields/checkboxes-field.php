<?php
/**
 * The template for displaying the checkboxes field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/checkboxes-field.php
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

<?php foreach ( $data->options as $opt_key => $value ) : ?>

	<label><input
		type="checkbox"
		class="input-checkbox"
		name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>[]"
		<?php
		if ( ! empty( $data->value ) && is_array( $data->value ) ) {
			checked( in_array( $opt_key, $data->value, true ), true );}
		?>
		value="<?php echo esc_attr( $opt_key ); ?>"
	/>
	<small class="description"><?php echo esc_html( $value ); ?></small></label>

<?php endforeach; ?>

<?php
if ( ! empty( $data->description ) ) :
	?>
	<small class="description"><?php echo wp_kses_post( $data->description ); ?></small><?php endif; ?>
