<?php
/**
 * Filters meant to be triggered everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Modify the url retrieved with wp_registration_url().
 *
 * @param string $url
 * @return void
 */
function wpum_set_registration_url( $url ) {
	return esc_url( get_permalink( wpum_get_core_page_id( 'register' ) ) );
}
add_filter( 'register_url', 'wpum_set_registration_url' );
