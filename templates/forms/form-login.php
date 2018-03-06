<?php
/**
 * The Template for displaying the login form.
 *
 * This template can be overridden by copying it to yourtheme/wpum/forms/form-login.php
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

<div class="wpum-template wpum-form wpum-login-form">

	<?php do_action( 'wpum_before_login_form' ); ?>

	<form action="<?php echo esc_url( $data->action ); ?>" method="post" id="wpum-submit-login-form" enctype="multipart/form-data">

		<?php foreach ( $data->fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo $field['label'] . apply_filters( 'wpum_form_required_label', $field['required'] ? '' : ' <small>' . __( '(optional)' ) . '</small>', $field ); ?></label>
				<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
					<?php
						// Add the key to field.
						$field[ 'key' ] = $key;
						WPUM()->templates
							->set_template_data( $field )
							->get_template_part( 'form-fields/' . $field['type'], 'field' );
					?>
				</div>
			</fieldset>
		<?php endforeach; ?>

		<input type="hidden" name="wpum_form" value="<?php echo $data->form; ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $data->step ); ?>" />
		<input type="submit" name="submit_login" class="button" value="Login" />

	</form>

	<?php do_action( 'wpum_after_login_form' ); ?>

</div>
