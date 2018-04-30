<?php
/**
 * The Template for displaying a given user's custom field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/elementor/custom-field.php
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

if( empty( $data->field->get_value() ) ) {
	return;
}

?>

<div class="wpum-custom-field">
	<?php if( $data->label ) : ?>
	<p <?php if( $data->inline ) : ?>class="wpum-elementor-label-inline"<?php endif; ?>><?php echo esc_html( $data->field->get_name() ); ?>:</p>
	<?php endif; ?>
	<span class="wpum-custom-field-value"><?php echo $data->field->get_value(); ?></span>
</div>
