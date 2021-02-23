<?php
/**
 * The template for displaying the uploaded content for a file field.
 *
 * This template can be overridden by copying it to yourtheme/wpum/form-fields/file-uploaded.php
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

<div class="wpum-uploaded-file">
	<input type="hidden" class="input-text" name="<?php echo esc_attr( $data->name ); ?>" value="<?php echo esc_attr( $data->value ); ?>" />
</div>
