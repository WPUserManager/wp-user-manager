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
	$rate_text = sprintf( __( 'Please support the future of <a href="%1$s" target="_blank">WP User Manager</a> by <a href="%2$s" target="_blank">rating us</a> on <a href="%2$s" target="_blank">WordPress.org</a>', 'wprm', 'wpum' ),
		'https://wpusermanager.com',
		'http://wordpress.org/support/view/plugin-reviews/wp-user-manager?filter=5#postform'
	);
	return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . ' <span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span><span class="dashicons dashicons-star-filled footer-star"></span></span>';
}
add_filter( 'admin_footer_text', 'wpum_admin_rate_us' );
