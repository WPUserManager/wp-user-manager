<div class="wrap" id="wpum-addons-page">
	<h1>
		<?php esc_html_e( 'WP User Manager Add-ons', 'wp-user-manager' ); ?>
		<a href="https://wpusermanager.com/addons/" class="button-primary" target="_blank">
			<?php esc_html_e( 'View All Add-ons', 'wp-user-manager' ); ?><span class="dashicons dashicons-external"></span>
		</a>
	</h1>

	<p><?php esc_html_e( 'The following Add-ons extend the functionality of WP User Manager.', 'wp-user-manager' ); ?></p>

	<div id="wpum-addons-list">

		<?php foreach( $this->get_addons() as $addon ) : ?>

			<div class="download type-download">
				<div class="featured-img">
					<a href="<?php echo esc_url( $addon->link ); ?>" title="<?php echo esc_html( $addon->title ); ?>" target="_blank">
						<img src="<?php echo esc_url( $addon->thumbnail ); ?>" alt="<?php echo esc_html( $addon->title ); ?>" width="280" height="164">
					</a>
				</div>
				<div class="addon-content">
					<h3 class="addon-heading">
						<a href="<?php echo esc_url( $addon->link ); ?>" title="<?php echo esc_html( $addon->title ); ?>" target="_blank"><?php echo esc_html( $addon->title ); ?></a>
					</h3>
					<p><?php echo esc_html( $addon->excerpt ); ?></p>
				</div>
				<div class="addon-footer-wrap give-clearfix">
					<a href="<?php echo esc_url( $addon->link ); ?>" title="<?php echo esc_html( $addon->title ); ?>" class="button" target="_blank"><?php echo esc_html__( 'Read more', 'wp-user-manager' ); ?> <span class="dashicons dashicons-external"></span></a>
				</div>
			</div>

		<?php endforeach; ?>

	</div>

</div>
