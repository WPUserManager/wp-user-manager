<div id="wpum-db-updates" data-resume-update="0">
	<div class="postbox-container">
		<div class="postbox">
			<h2 class="hndle"><?php esc_html_e( 'Database Updates', 'wp-user-manager' ); ?></h2>
			<div class="inside">
				<div class="progress-container">
					<p class="update-message"><strong><?php esc_html_e( 'Updates Completed.', 'wp-user-manager' ) ?></strong></p>
					<div class="progress-content">
						<div class="notice-wrap wpum-clearfix">
							<div class="notice notice-success is-dismissible inline">
								<p><?php esc_html_e( 'WP User Manager database updates completed successfully. Thank you for updating to the latest version!', 'wp-user-manager' ) ?>
								</p>
								<button type="button" class="notice-dismiss"></button>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- .inside -->
		</div><!-- .postbox -->
	</div>
</div>
<?php delete_option( 'wpum_show_db_upgrade_complete_notice' ); ?>
