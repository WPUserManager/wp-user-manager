<?php
/**
 * Filters meant to be triggered within the administration only.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modify the WordPress admin footer within WPUM powered pages.
 *
 * @param string $footer_text original text.
 * @return string
 */
function wpum_admin_rate_us( $footer_text ) {
	$screen = get_current_screen();
	if ( $screen->base !== 'users_page_wpum-settings' )
		return;
	$rate_text = sprintf( __( 'Please support the future of <a href="%1$s" target="_blank">WP User Manager</a> by <a href="%2$s" target="_blank">rating us</a> on <a href="%2$s" target="_blank">WordPress.org</a>', 'wpum' ),
		'https://wpusermanager.com',
		'http://wordpress.org/support/view/plugin-reviews/wp-user-manager?filter=5#postform'
	);
	return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . ' <span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span></span>';
}
add_filter( 'admin_footer_text', 'wpum_admin_rate_us' );

/**
 * Add new links to the plugin's action links list.
 *
 * @since 1.0.0
 * @return array
 */
function wpum_add_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'users.php?page=wpum-settings' ) . '">' . __( 'Settings','wpum' ) . '</a>';
	$docs_link     = '<a href="https://docs.wpusermanager.com/" target="_blank">' . __( 'Documentation','wpum' ) . '</a>';
	$addons_link   = '<a href="http://wpusermanager.com/addons" target="_blank">' . __( 'Addons','wpum' ) . '</a>';
	array_unshift( $links, $settings_link );
	array_unshift( $links, $docs_link );
	array_unshift( $links, $addons_link );
	return $links;
}
add_filter( 'plugin_action_links_' . WPUM_SLUG, 'wpum_add_settings_link' );
