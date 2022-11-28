<?php
/**
 * The Template for displaying the profile comments tab content.
 *
 * This template can be overridden by copying it to yourtheme/wpum/profiles/comments.php
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

$user_comments = wpum_get_comments_for_profile( $data->user->ID );

?>

<div id="profile-comments">

	<?php
	if ( ! empty( $user_comments ) ) :
		foreach ( $user_comments as $user_comment ) :
			?>

			<div class="wpum-single-comment" id="wpum-comment-<?php echo esc_attr( $user_comment->comment_ID ); ?>">

			<?php
			$user_comment_content = wp_trim_words( $user_comment->comment_content, 13 );
			$the_post             = get_the_title( $user_comment->comment_post_ID );
			$the_permalink        = get_post_permalink( $user_comment->comment_post_ID );
			$the_date             = get_comment_date( get_option( 'date_format' ), $user_comment->comment_ID );
			?>

			<p>
				<?php
				// translators: %1$s user comment count %2$s post permalink %3$s post %4$s post date
				echo wp_kses_post( sprintf( _x( '"%1$s" on <a href="%2$s">%3$s</a>, %4$s.', 'This text displays the comments left by the user on his profile page.', 'wp-user-manager' ), $user_comment_content, $the_permalink, $the_post, $the_date ) );
				?>
			</p>

			</div>

			<?php
			endforeach;

		else :

			WPUM()->templates
				->set_template_data( array(
					// translators: %s user display name
					'message' => sprintf( esc_html__( '%s has not made any comment yet.', 'wp-user-manager' ), $data->user->display_name ),
				) )
				->get_template_part( 'messages/general', 'warning' );

			endif;

		?>

</div>
