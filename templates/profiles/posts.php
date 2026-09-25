<?php
/**
 * The Template for displaying the profile posts tab content.
 *
 * This template can be overridden by copying it to yourtheme/wpum/profiles/posts.php
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

$the_query = wpum_get_posts_for_profile( $data->user->ID );

?>

<div id="profile-posts">

	<?php if ( $the_query->have_posts() ) : ?>

		<?php
		while ( $the_query->have_posts() ) :
			$the_query->the_post();
			?>

			<div class="wpum-post" id="wpum-post-<?php echo esc_attr( the_id() ); ?>">
				<?php if ( apply_filters( 'wpum_profile_posts_display_thumbnail', false ) && has_post_thumbnail() ) : ?>
					<div class="wpum-post-thumbnail">
					<a href="<?php the_permalink(); ?>" class="wpum-post-title">
						<?php echo get_the_post_thumbnail(); ?></a>
					</div>
				<?php endif; ?>
				<a href="<?php the_permalink(); ?>" class="wpum-post-title"><?php the_title(); ?></a>
				<?php do_action( 'wpum_profile_posts_after_title' ); ?>
				<ul class="wpum-post-meta">
					<li>
						<strong><?php esc_html_e( 'Posted on:', 'wp-user-manager' ); ?></strong>
						<?php echo get_the_date(); ?> -
					</li>
					<li>
						<strong><?php esc_html_e( 'Comments:', 'wp-user-manager' ); ?></strong>
						<?php comments_popup_link( esc_html__( 'No Comments', 'wp-user-manager' ), esc_html__( '1 Comment', 'wp-user-manager' ), esc_html__( '% Comments', 'wp-user-manager' ) ); ?>
					</li>
				</ul>
				<?php do_action( 'wpum_profile_posts_after_meta' ); ?>
			</div>

		<?php endwhile; ?>

		<div id="profile-pagination">
			<?php
				echo wp_kses_post( paginate_links( array(
					'base'         => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
					'total'        => $the_query->max_num_pages,
					'current'      => max( 1, get_query_var( 'paged' ) ),
					'format'       => '?paged=%#%',
					'show_all'     => false,
					'type'         => 'plain',
					'end_size'     => 2,
					'mid_size'     => 1,
					'prev_next'    => true,
					'prev_text'    => sprintf( '<i></i> %1$s', esc_html__( 'Newer Posts', 'wp-user-manager' ) ),
					'next_text'    => sprintf( '%1$s <i></i>', esc_html__( 'Older Posts', 'wp-user-manager' ) ),
					'add_args'     => false,
					'add_fragment' => '',
				) ) );
			?>
		</div>

		<?php wp_reset_postdata(); ?>

	<?php else : ?>

		<?php
			WPUM()->templates
				->set_template_data( array(
					// translators: %s user display name
					'message' => sprintf( esc_html__( '%s has not submitted any posts yet.', 'wp-user-manager' ), apply_filters( 'wpum_user_display_name', $data->user->display_name, $data->user ) ),
				) )
				->get_template_part( 'messages/general', 'warning' );
		?>

	<?php endif; ?>

</div>

