<?php
/**
 * The Template for displaying the account forms.
 *
 * This template can be overridden by copying it to yourtheme/wpum/forms/form-account.php
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

<div class="wpum-template wpum-form wpum-account-form">

	<h2><?php echo esc_html( $data->step_name ); ?></h2>

	<?php
	$updated = filter_input( INPUT_GET, 'updated', FILTER_SANITIZE_STRING );
	if ( 'success' === $updated ) :
		WPUM()->templates
			->set_template_data( array( 'message' => esc_html__( 'Profile successfully updated.', 'wp-user-manager' ) ) )
			->get_template_part( 'messages/general', 'success' );
	endif;
	?>

	<?php
	$password_updated = filter_input( INPUT_GET, 'password-updated', FILTER_SANITIZE_STRING );
	if ( 'success' === $password_updated ) :
		WPUM()->templates
			->set_template_data( array( 'message' => esc_html__( 'Password successfully updated.', 'wp-user-manager' ) ) )
			->get_template_part( 'messages/general', 'success' );
	endif;
	?>

	<?php do_action( 'wpum_before_account_form' ); ?>

		<form action="<?php echo esc_url( $data->action ); ?>" method="post" id="wpum-submit-account-form" enctype="multipart/form-data">

			<?php foreach ( $data->fields as $key => $field ) : ?>

				<?php
				// Parent field should handle the child field rendering
				if ( in_array( $field['type'], wpum_get_registered_parent_field_types(), true ) ) {

					$field['key'] = $key;
					$template     = isset( $field['template'] ) ? $field['template'] : $field['type'];

					WPUM()->templates
						->set_template_data( $field )
						->get_template_part( 'form-fields/' . $template, 'field' );

					continue;
				}
				?>

				<fieldset class="fieldset-<?php echo esc_attr( $key ); ?>">

					<?php if ( 'checkbox' === $field['type'] ) : ?>

						<label for="<?php echo esc_attr( $key ); ?>">
							<span class="field <?php echo $field['required'] ? 'required-field' : ''; ?>">
								<?php
									// Add the key to field.
									$field['key'] = $key;
									WPUM()->templates
										->set_template_data( $field )
										->get_template_part( 'form-fields/' . $field['template'], 'field' );
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
									->get_template_part( 'form-fields/' . $field['template'], 'field' );
							?>
						</div>

					<?php endif; ?>

				</fieldset>
			<?php endforeach; ?>

			<input type="hidden" name="wpum_form" value="<?php echo esc_attr( $data->form ); ?>" />
			<input type="hidden" name="step" value="<?php echo esc_attr( $data->step ); ?>" />
			<?php wp_nonce_field( 'verify_account_form', 'account_update_nonce' ); ?>
			<input type="submit" name="submit_account" class="button" value="<?php esc_html_e( 'Update profile', 'wp-user-manager' ); ?>" />

		<?php do_action( 'wpum_after_account_form', $data ); ?>

	</form>

</div>
