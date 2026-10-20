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
 * @version 2.10.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only pre-select an option when the field has a default. Pre-selecting the first
// option chose an answer for the user (#153); the filter restores that behaviour.
// A saved or default value of "0" is a real answer, so only treat null and '' as missing.
$wpum_radio_has = function ( $value ) {
	return is_scalar( $value ) && '' !== (string) $value;
};

$has_value   = isset( $data->value ) && $wpum_radio_has( $data->value );
$has_default = isset( $data->default ) && $wpum_radio_has( $data->default );

if ( ! $has_default && apply_filters( 'wpum_radio_field_select_first_option', false, $data ) ) {
	$data->default = current( array_keys( $data->options ) );
	$has_default   = true;
}

$default = $has_value ? $data->value : ( $has_default ? $data->default : '' );

foreach ( $data->options as $option_key => $value ) : ?>

	<label><input type="radio" name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>" value="<?php echo esc_attr( $option_key ); ?>" <?php checked( $default, $option_key ); ?> /> <?php echo esc_html( $value ); ?></label>

<?php endforeach; ?>

<?php
if ( ! empty( $data->description ) ) :
	?>
	<small class="description"><?php echo wp_kses_post( $data->description ); ?></small><?php endif; ?>
