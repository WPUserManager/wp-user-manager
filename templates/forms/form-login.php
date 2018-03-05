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
?>

<div class="wpum-template wpum-form wpum-login-form">

	<?php do_action( 'wpum_before_login_form' ); ?>

	<form action="#" method="post">
		<p>
			<label for="user-login"><?php echo esc_html( $data->login_label ); ?></label>
			<input type="text" class="wpum-form-input" name="user-login" id="user-login">
		</p>
		<p>
			<label for="user-password"><?php esc_html_e( 'Password' ); ?></label>
			<input type="password" class="wpum-form-input" name="user-password" id="user-password">
		</p>
		<p>
			<input type="submit" class="button button-primary" value="<?php esc_html_e( 'Login' ); ?>">
			<span>
				<label for="remember-me">
					<input type="checkbox" name="remember-me" id="remember-me"> <?php esc_html_e( 'Remember me' ); ?>
				</label>
			</span>
		</p>
	</form>

	<?php do_action( 'wpum_after_login_form' ); ?>

</div>
