<?php
/**
 * The Template for displaying the directory single user item loop.
 *
 * This template can be overridden by copying it to yourtheme/wpum/directory/single-user.php
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

$user = $data->data;

?>
<div class="wpum-directory-single-user">
	<div class="wpum-row wpum-middle-xs">
		<div class="wpum-col-xs-2" id="directory-avatar">
			<a href="<?php echo esc_url( wpum_get_profile_url( $user ) ); ?>">
				<?php echo get_avatar( $user->ID, 100 ); ?>
			</a>
		</div>
		<div class="wpum-col-xs-6">
			<p class="wpum-name">
				<a href="<?php echo esc_url( wpum_get_profile_url( $user ) ); ?>"><?php echo esc_html( apply_filters( 'wpum_user_display_name', $user->display_name, $user ) ); ?></a>
			</p>
			<p class="wpum-description">
				<?php echo wp_kses_post( wp_trim_words( get_user_meta( $user->ID, 'description', true ), $num_words = 20, '...' ) ); ?>
			</p>
		</div>
		<div class="wpum-col-xs-4 wpum-meta">
			<a href="<?php echo esc_url( wpum_get_profile_url( $user ) ); ?>" class="button"><?php esc_html_e( 'View profile', 'wp-user-manager' ); ?></a>
		</div>
	</div>
</div>
