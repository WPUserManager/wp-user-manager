<?php
/**
 * Actions meant to be triggered in the admin panel only.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Delete cached list of pages when a page is updated or created.
 * This is needed to refresh the list of available pages for the options panel.
 *
 * @param string $post_id
 * @return void
 */
function wpum_delete_pages_transient( $post_id ) {

	if ( wp_is_post_revision( $post_id ) )
		return;

	delete_transient( 'wpum_get_pages' );

}
add_action( 'save_post_page', 'wpum_delete_pages_transient' );

/**
 * Add WPUM specific admin bar links.
 *
 * @param object $wp_admin_bar
 * @return void
 */
function wpum_admin_bar_menu( $wp_admin_bar ) {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$args = array(
		'id'    => 'wpum_node',
		'href'  => admin_url( 'users.php' ),
		'title' => '<span class="ab-icon dashicons dashicons-admin-users" style="margin-top:2px"></span>' . esc_html__( ' Users' ),
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_emails',
		'href'   => admin_url( 'users.php?page=wpum-emails' ),
		'title'  => esc_html__( 'Emails' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_settings',
		'href'   => admin_url( 'users.php?page=wpum-settings' ),
		'title'  => esc_html__( 'Settings' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

}
add_action( 'admin_bar_menu', 'wpum_admin_bar_menu', 100 );

/**
 * Highlight registration and default registration role within the users page.
 *
 * @return void
 */
function wpum_show_registration_details() {

	$status              = get_option( 'users_can_register' );
	$role                = get_option('default_role');
	$enabled_or_disabled = ( $status ) ? esc_html__( 'enabled' ) : esc_html__( 'disabled' );

	?>
	<div class="notice wpum-registration-status">
		<p><?php esc_html_e( 'Registration status:' ); ?> <span class="<?php if( $status ) : ?>enabled<?php else : ?>disabled<?php endif; ?>"><?php echo $enabled_or_disabled; ?></span> <?php esc_html_e( 'Default registration role:' ); ?> <span><?php echo esc_html( $role ); ?></span></p>
    </div>
	<?php

}
add_action( 'admin_notices', 'wpum_show_registration_details' );
