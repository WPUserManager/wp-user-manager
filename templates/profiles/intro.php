<?php
/**
 * The Template for displaying the profile intro details.
 *
 * This template can be overridden by copying it to yourtheme/wpum/profiles/intro.php
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
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<div id="header-profile-details">

	<div class="wpum-row">
		<div class="wpum_one_fifth" id="header-avatar-container">
			<a href="<?php echo esc_url( wpum_get_profile_url( $data->user ) ); ?>">
				<?php echo get_avatar( $data->user->ID, 128 ); ?>
			</a>
		</div>
		<div class="wpum_four_fifth last" id="header-details-container">
			<div id="header-name-container">
				<h2>
					<?php echo esc_html( $data->user->display_name ); ?>
					<?php if( $data->current_user_id === $data->user->ID ) : ?>
						<a href="<?php echo esc_url( get_permalink( wpum_get_core_page_id( 'account' ) ) ); ?>"><small><?php esc_html_e( '( Edit account )' ); ?></small></a>
					<?php endif; ?>
				</h2>
			</div>
			<div id="header-description-container">
				<?php echo wpautop( get_user_meta( $data->user->ID, 'description', true ) ); ?>
			</div>
		</div>
		<div class="wpum_clearfix"></div>
	</div>

</div>
