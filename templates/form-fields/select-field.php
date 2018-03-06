<?php
/**
 * The template for displaying the select field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/select-field.php
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

?>

<select name="<?php echo esc_attr( isset( $data->name ) ? $data->name : $data->key ); ?>" id="<?php echo esc_attr( $data->key ); ?>" <?php if ( ! empty( $data->required ) ) echo 'required'; ?>>
	<?php foreach ( $data->options as $key => $value ) : ?>
		<option value="<?php echo esc_attr( $key ); ?>" <?php if ( isset( $data->value ) || isset( $data->default ) ) selected( isset( $data->value ) ? $data->value : $data->default, $key ); ?>><?php echo esc_html( $value ); ?></option>
	<?php endforeach; ?>
</select>
<?php if ( ! empty( $data->description ) ) : ?><small class="description"><?php echo $data->description; ?></small><?php endif; ?>
