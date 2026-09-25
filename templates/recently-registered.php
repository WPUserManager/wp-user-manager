<?php
/**
 * The Template for displaying the recently registered user list.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get the query
$users = wpum_get_recent_users( $data->amount );

?>

<div id="wpum-recent-users">

	<?php if ( $users ) : ?>

		<ul class="wpum-users-list">
			<?php foreach ( $users as $user ) : ?>

				<li>
					<?php if ( true === $data->link_to_profile || 'yes' === $data->link_to_profile ) : ?>
						<a href="<?php echo esc_url( wpum_get_profile_url( $user ) ); ?>"><?php echo esc_html( apply_filters( 'wpum_user_display_name', $user->display_name, $user ) ); ?></a>
					<?php else : ?>
						<?php echo esc_html( apply_filters( 'wpum_user_display_name', $user->display_name, $user ) ); ?>
					<?php endif; ?>
				</li>

			<?php endforeach; ?>
		</ul>

	<?php else : ?>

	<?php endif; ?>

</div>
