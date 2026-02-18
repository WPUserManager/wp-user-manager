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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$referrer = wp_get_referer();

$redirect_to = filter_input( INPUT_GET, 'redirect_to' );
if ( $redirect_to ) {
	$referrer = wp_validate_redirect( esc_url( $redirect_to ) );
}

?>

<div class="wpum-template wpum-form wpum-login-form">

	<?php do_action( 'wpum_before_login_form' ); ?>

	<form action="<?php echo esc_url( $data->action ); ?>" method="post" id="wpum-submit-login-form" enctype="multipart/form-data">

		<?php foreach ( $data->fields as $key => $field ) : ?>
			<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">

				<?php if ( 'checkbox' === $field['type'] ) : ?>

					<label for="<?php echo esc_attr( $key ); ?>">
						<span class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
							<?php
								// Add the key to field.
								$field['key'] = $key;
								WPUM()->templates
									->set_template_data( $field )
									->get_template_part( 'form-fields/' . $field['type'], 'field' );
							?>
						</span>
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( isset( $field['required'] ) && $field['required'] ) : ?>
							<span class="wpum-required">*</span>
						<?php endif; ?>
					</label>

				<?php else : ?>

					<label for="<?php echo esc_attr( $key ); ?>">
						<?php echo esc_html( $field['label'] ); ?>
						<?php if ( isset( $field['required'] ) && $field['required'] ) : ?>
							<span class="wpum-required">*</span>
						<?php endif; ?>
					</label>
					<div class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
						<?php
							// Add the key to field.
							$field['key'] = $key;
							WPUM()->templates
								->set_template_data( $field )
								->get_template_part( 'form-fields/' . $field['type'], 'field' );
						?>
					</div>

				<?php endif; ?>

			</fieldset>
		<?php endforeach; ?>

		<input type="hidden" name="wpum_form" value="<?php echo esc_attr( $data->form ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $data->step ); ?>" />
		<input type="hidden" name="submit_referrer" value="<?php echo esc_url( $referrer ); ?>" />

		<?php do_action( 'wpum_before_submit_button_login_form' ); ?>

		<input type="submit" name="submit_login" class="button" value="<?php esc_html_e( 'Login', 'wp-user-manager' ); ?>" />

	</form>

	<?php do_action( 'wpum_after_login_form' ); ?>

</div>
