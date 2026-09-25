<?php
/**
 * The Template for displaying the directory
 *
 * This template can be overridden by copying it to yourtheme/wpum/directory.php
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

<div id="wpum-user-directory">

	<?php do_action( 'wpum_before_user_directory', $data ); ?>

	<form action="<?php the_permalink(); ?>" method="GET" name="wpum-directory-search-form">
		<?php
			WPUM()->templates
				->set_template_data( $data )
				->get_template_part( 'directory/search-form' );
			WPUM()->templates
				->set_template_data( $data )
				->get_template_part( 'directory/top-bar' );
		?>
	</form>
	<!-- start directory -->
	<div id="wpum-directory-users-list">

		<?php if ( is_array( $data->results ) && ! empty( $data->results ) ) : ?>

			<?php foreach ( $data->results as $user ) : ?>
				<?php

					$user_template = ( 'default' !== $data->user_template || ! $data->user_template ) ? $data->user_template : 'user';

					WPUM()->templates
						->set_template_data( $user )
						->get_template_part( 'directory/single', $user_template );
				?>
			<?php endforeach; ?>

			<?php wpum_user_directory_pagination( $data ); ?>

		<?php else : ?>
			<?php

				WPUM()->templates
					->set_template_data( array(
						'message' => esc_html__( 'No users have been found.', 'wp-user-manager' ),
					) )
					->get_template_part( 'messages/general', 'warning' );

			?>

		<?php endif; ?>

	</div>
	<!-- end directory -->
	<?php do_action( 'wpum_after_user_directory', $data ); ?>

</div>
