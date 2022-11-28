<?php
/**
 * The Template for displaying the current user overview.
 *
 * This template can be overridden by copying it to yourtheme/wpum/user-overview.php
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

$user = wp_get_current_user();

?>

<div id="wpum-user-overview">
	<div class="wpum-row">
		<div class="wpum-col-xs-3" id="avatar">
			<?php echo get_avatar( $user->data->ID, 100 ); ?>
		</div>
		<div class="wpum-col-xs-9">
			<span>
				<strong><?php echo esc_html( $user->display_name ); ?></strong>
			</span>
			<ul>
				<li>
					<?php
					$edit_account_text = apply_filters( 'wpum_profile_edit_account_text', 'Edit account', $user->data->ID );
					?>
					<a href="<?php echo esc_url( get_permalink( wpum_get_core_page_id( 'account' ) ) ); ?>"><?php echo esc_html( $edit_account_text ); ?></a>
				</li>
				<li>|</li>
				<li>
					<a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php echo esc_html__( 'Logout', 'wp-user-manager' ); ?></a>
				</li>
			</ul>
		</div>
	</div>
</div>
