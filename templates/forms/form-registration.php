<?php
/**
 * The Template for displaying the registration forms.
 *
 * This template can be overridden by copying it to yourtheme/wpum/forms/form-registration.php
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

<div class="wpum-template wpum-form wpum-registration-form">

	<?php do_action( 'wpum_before_registration_form', $data ); ?>

	<form action="<?php echo esc_url( $data->action ); ?>" method="post" id="wpum-submit-registration-form" enctype="multipart/form-data">

		<?php foreach ( $data->fields as $key => $field ) : ?>

			<?php
			/**
			 * Hook to render form field. Always use conditional check to
			 * make sure the field type. Otherwise field would render multiple times.
			 *
			 * @var $field
			 */
			do_action( 'wpum_registration_form_field', $field, $key, $data->fields );
			?>

		<?php endforeach; ?>

		<input type="hidden" name="wpum_form" value="<?php echo esc_attr( $data->form ); ?>" />
		<input type="hidden" name="step" value="<?php echo esc_attr( $data->step ); ?>" />
		<?php wp_nonce_field( 'verify_registration_form', 'registration_nonce' ); ?>

		<?php do_action( 'wpum_before_submit_button_registration_form', $data ); ?>

		<?php
		$label = isset( $data->submit_label ) ? $data->submit_label : esc_html__( 'Register', 'wp-user-manager' );
		?>
		<input type="submit" name="submit_registration" class="button"
			   value="<?php echo esc_html( apply_filters( 'wpum_registration_form_submit_label', $label ) ); ?>"/>

	</form>

	<?php do_action( 'wpum_after_registration_form', $data ); ?>

</div>
