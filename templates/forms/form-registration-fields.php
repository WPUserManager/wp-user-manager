<?php
/**
 * The Template for displaying the registration forms built in fields.
 *
 * This template can be overridden by copying it to yourtheme/wpum/forms/form-registration-fields.php
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

$field = $data->field;
$key   = $data->key;
?>

<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">

	<?php if( $field['type'] == 'checkbox' ) : ?>

		<label for="<?php echo esc_attr( $key ); ?>">
			<span class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
				<?php
					// Add the key to field.
					$field[ 'key' ] = $key;
					$template = isset( $field['template'] ) ? $field['template'] : $field['type'];
					WPUM()->templates
						->set_template_data( $field )
						->get_template_part( 'form-fields/' . $template, 'field' );
				?>
			</span>
			<?php echo esc_html( $field['label'] ); ?>
			<?php if( isset( $field['required'] ) && $field['required'] ) : ?>
				<span class="wpum-required">*</span>
			<?php endif; ?>
		</label>

	<?php else : ?>

		<label for="<?php echo esc_attr( $key ); ?>">
			<?php echo esc_html( $field['label'] ); ?>
			<?php if( isset( $field['required'] ) && $field['required'] ) : ?>
				<span class="wpum-required">*</span>
			<?php endif; ?>
		</label>
		<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
			<?php
				// Add the key to field.
				$field[ 'key' ] = $key;
				$template = isset( $field['template'] ) ? $field['template'] : $field['type'];
				WPUM()->templates
					->set_template_data( $field )
					->get_template_part( 'form-fields/' . $template, 'field' );
			?>
		</div>

	<?php endif; ?>

</fieldset>
