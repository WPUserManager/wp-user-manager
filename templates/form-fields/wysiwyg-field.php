<?php
/**
 * The template for displaying the WYSIWYG field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/textarea-field.php
 *
 * HOWEVER, on occasion WPUM will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 2.3.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field_settings = property_exists( $data, 'field_settings' ) ? $data->field_settings : array();
?>

<?php wp_editor( $data->value, esc_attr( $data->key ), $field_settings ); ?>
<?php
if ( ! empty( $data->description ) ) :
	?>
	<small class="description"><?php echo wp_kses_post( $data->description ); ?></small><?php endif; ?>
