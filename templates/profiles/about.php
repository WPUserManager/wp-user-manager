<?php
/**
 * The Template for displaying the profile about tab content.
 *
 * This template can be overridden by copying it to yourtheme/wpum/profiles/about.php
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

<div id="profile-content-about" class="profile-content-settings">

	<?php if ( wpum_has_profile_fields() ) : ?>

		<?php
		while ( wpum_profile_field_groups() ) :
			wpum_the_profile_field_group();
			?>

			<?php if ( wpum_field_group_has_fields( true ) ) : ?>
				<div class="profile-fields-group profile-fields-group-<?php echo esc_attr( wpum_get_field_group_id() ); ?>">
				<?php if ( wpum_get_field_group_name() ) : ?>
					<h3 class="group-title"><?php echo esc_html( wpum_get_field_group_name() ); ?></h3>
				<?php endif; ?>

				<?php if ( ! empty( wpum_get_field_group_description() ) ) : ?>
					<p class="group-description"><?php wpum_the_field_group_description(); ?></p>
				<?php endif; ?>

				<table class="profile-fields-table">
					<tbody>
					<?php
					while ( wpum_profile_fields() ) :
						wpum_the_profile_field();
						?>
						<?php if ( wpum_field_has_data() && apply_filters( 'wpum_profile_display_field', true ) ) : ?>
							<tr class="<?php wpum_the_field_css_class(); ?>">
								<td class="label"><?php wpum_the_field_name(); ?></td>
								<td class="data"><?php wpum_the_field_value(); ?></td>
							</tr>
						<?php endif; ?>
					<?php endwhile; ?>
					</tbody>
				</table>
				</div>
			<?php endif; ?>

		<?php endwhile; ?>

	<?php endif; ?>

</div>
