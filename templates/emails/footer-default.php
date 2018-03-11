<?php
/**
 * The Template for displaying the footer section of the emails.
 *
 * This template can be overridden by copying it to yourtheme/wpum/emails/footer-default.php
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
<tr>
	<td style="box-sizing: border-box; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; word-break: break-word;">
		<table class="email-footer" align="center" width="570" cellpadding="0" cellspacing="0" style="box-sizing: border-box; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; margin: 0 auto; padding: 0; text-align: center; width: 570px;">
			<tr>
				<td class="content-cell" align="center" style="box-sizing: border-box; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; padding: 35px; word-break: break-word;">
					<p class="sub align-center" style="box-sizing: border-box; color: #AEAEAE; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; font-size: 12px; line-height: 1.5em; margin-top: 0;" align="center">
						<?php echo wpautop( wp_kses_post( wptexturize( apply_filters( 'wpum_email_footer_text', '<a href="' . esc_url( home_url() ) . '">' . get_bloginfo( 'name' ) . '</a>' ) ) ) ); ?>
					</p>
					<p class="sub align-center" style="box-sizing: border-box; color: #AEAEAE; font-family: Arial, 'Helvetica Neue', Helvetica, sans-serif; font-size: 12px; line-height: 1.5em; margin-top: 0;" align="center">
						[Company Name, LLC]
						<br />1234 Street Rd.
						<br />Suite 1234
					</p>
				</td>
			</tr>
		</table>
	</td>
</tr>
</table>
</td>
</tr>
</table>
</body>
</html>
