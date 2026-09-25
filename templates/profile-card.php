<?php
/**
 * The Template for displaying the profile card.
 *
 * This template can be overridden by copying it to yourtheme/wpum/profile-card.php
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

$user = get_user_by( 'id', $data->user_id );

?>

<div id="wpum-profile-card" class="wpum-profile-card">

	<?php if ( 'yes' === $data->display_cover ) : ?>
		<div class="wpum-profile-cover" style="background-image: url(<?php echo esc_url( get_user_meta( $user->data->ID, 'user_cover', true ) ); ?>);"></div>
	<?php endif; ?>

	<div class="wpum-profile-img">
		<?php echo get_avatar( $user->data->ID, 100 ); ?>
	</div>

	<div class="wpum-card-details">

		<h4 class="wpum-card-name"><?php echo esc_html( apply_filters( 'wpum_user_display_name', $user->data->display_name, $user ) ); ?></h4>

		<?php do_action( 'wpum_profile_card_details', $user ); ?>

		<?php if ( 'yes' === $data->display_buttons ) : ?>
			<ul>
				<?php if ( 'yes' === $data->link_to_profile ) : ?>
					<li><a href="#" class="wpum-card-button"><?php esc_html_e( 'View Profile', 'wp-user-manager' ); ?></a></li>
				<?php endif; ?>
				<li>
					<a href="<?php echo esc_url( wp_logout_url() ); ?>" class="wpum-card-button"><?php esc_html_e( 'Logout', 'wp-user-manager' ); ?></a>
				</li>
				<?php do_action( 'wpum_profile_card_buttons', $user ); ?>
			</ul>
		<?php endif; ?>

	</div>

</div>
