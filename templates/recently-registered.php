<?php
/**
 * The Template for displaying the recently registered user list.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Get the query
$users = wpum_get_recent_users( $data->amount );

?>

<div id="wpum-recent-users">

	<?php if ( $users ) : ?>

		<ul class="wpum-users-list">
			<?php foreach ( $users as $user ) : ?>

				<li>
					<?php if ( $data->link_to_profile == 'yes' ) : ?>
						<a href="<?php echo wpum_get_profile_url( $user ); ?>"><?php echo $user->display_name; ?></a>
					<?php else : ?>
						<?php echo $user->display_name; ?>
					<?php endif; ?>
				</li>

			<?php endforeach; ?>
		</ul>

	<?php else : ?>

	<?php endif; ?>

</div>
