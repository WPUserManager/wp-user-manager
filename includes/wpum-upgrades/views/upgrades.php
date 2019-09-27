<?php
/**
 * Upgrade Screen
 *
 * @license https://opensource.org/licenses/gpl-license GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wpum_updates = WPUM_Updates::get_instance();
?>
<div class="wrap" id="poststuff">
	<div id="wpum-updates">
		<h1 id="wpum-updates-h1"><?php esc_html_e( 'WP User Manager - Updates', 'wp-user-manager' ); ?></h1>
		<hr class="wp-header-end"

		<?php $db_updates = $wpum_updates->get_pending_db_update_count(); ?>
		<?php if ( ! empty( $db_updates ) ) : ?>
			<?php
			$is_doing_updates = $wpum_updates->is_doing_updates();
			$db_update_url    = add_query_arg( array( 'type' => 'database', ) );
			$resume_updates   = get_option( 'wpum_doing_upgrade' );
			$width            = ! empty( $resume_updates ) ? $resume_updates['percentage'] : 0;
			?>
			<div class="wpum-update-panel-content">
				<p><?php printf( __( 'WP User Manager regularly receives new features, bug fixes, and enhancements. It is important to always stay up-to-date with latest version of WPUM core and its addons. Please create a backup of your site before updating. To update add-ons be sure your <a href="%1$s">license keys</a> are activated.', 'wp-user-manager' ), 'https://wpumwp.com/my-account/' ); ?></p>
			</div>

			<div id="wpum-db-updates" data-resume-update="<?php echo absint( $wpum_updates->is_doing_updates() ); ?>">
				<div class="postbox-container">
					<div class="postbox">
						<h2 class="hndle"><?php _e( 'Database Updates', 'wp-user-manager' ); ?></h2>
						<div class="inside">
							<div class="panel-content">
								<p class="wpum-update-button">
									<span class="wpum-doing-update-text-p" <?php echo WPUM_Updates::$background_updater->is_paused_process() ? 'style="display:none;"' : '';  ?>>
										<?php echo sprintf(
										__( '%1$s <a href="%2$s" class="wpum-update-now %3$s">%4$s</a>', 'wp-user-manager' ),
										$is_doing_updates ?
											__( 'WP User Manager is currently updating the database in the background.', 'wp-user-manager' ) :
											__( 'WP User Manager needs to update the database.', 'wp-user-manager' ),
										$db_update_url,
										( $is_doing_updates ? 'wpum-hidden' : '' ),
										__( 'Update now', 'wp-user-manager' )
									);
									?>
									</span>
									<span class="wpum-update-paused-text-p" <?php echo ! WPUM_Updates::$background_updater->is_paused_process()  ? 'style="display:none;"' : '';  ?>>
										<?php if ( get_option( 'wpum_upgrade_error' ) ) : ?>
											&nbsp;<?php _e( 'An unexpected issue occurred during the database update which caused it to stop automatically. Please contact support for assistance.', 'wp-user-manager' ); ?>
										<?php else : ?>
											<?php _e( 'The updates have been paused.', 'wp-user-manager' ); ?>
										<?php endif; ?>
									</span>
								</p>
							</div>
							<div class="progress-container<?php echo $is_doing_updates ? '' : ' wpum-hidden'; ?>">
								<p class="update-message">
									<strong>
										<?php
										echo sprintf(
											__( 'Update %s of %s', 'wp-user-manager' ),
											$wpum_updates->get_running_db_update(),
											$wpum_updates->get_total_new_db_update_count()
										);
										?>
									</strong>
								</p>
								<div class="progress-content">
									<?php if ( $is_doing_updates  ) : ?>
										<div class="notice-wrap wpum-clearfix">

											<?php if ( ! WPUM_Updates::$background_updater->is_paused_process() ) :  ?>
												<span class="wpum-spinner spinner is-active"></span>
											<?php endif; ?>

											<div class="wpum-progress">
												<div style="width: <?php echo $width ?>%;"></div>
											</div>
										</div>
									<?php endif; ?>
								</div>
							</div>

							<?php if ( ! $is_doing_updates ) : ?>
								<div class="wpum-run-database-update"></div>
							<?php endif; ?>
						</div>
						<!-- .inside -->
					</div><!-- .postbox -->
				</div>
			</div>
			<?php else: include WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/views/db-upgrades-complete-metabox.php';?>
		<?php endif; ?>

		<?php $plugin_updates = $wpum_updates->get_total_plugin_update_count(); ?>
		<?php if ( ! empty( $plugin_updates ) ) : ?>
			<?php $plugin_update_url = add_query_arg( array(
				'plugin_status' => 'wpum',
			), admin_url( '/plugins.php' ) ); ?>
			<div id="wpum-plugin-updates">
				<div class="postbox-container">
					<div class="postbox">
						<h2 class="hndle"><?php _e( 'Addon Updates', 'wp-user-manager' ); ?></h2>
						<div class="inside">
							<div class="panel-content">
								<p>
									<?php
									printf(
										_n(
											'There is %1$d WP User Manager addon that needs to be updated. <a href="%2$s">Update now</a>',
											'There are %1$d WP User Manager addons that need to be updated. <a href="%2$s">Update now</a>',
											$plugin_updates,
											'wp-user-manager'
										),
										$plugin_updates,
										$plugin_update_url
									);
									?>
								</p>
								<?php include_once 'plugins-update-section.php'; ?>
							</div>
						</div>
						<!-- .inside -->
					</div><!-- .postbox -->
				</div>
			</div>
		<?php endif; ?>

	</div>
</div>
