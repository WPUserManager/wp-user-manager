<?php
/**
 * Functions collection to work with directories.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve the list of options for the sort by dropdown on the user directory.
 *
 * @return array
 */
function wpum_get_directory_sort_by_methods() {

	$options = array(
		'newest'    => esc_html__( 'Newest users first', 'wp-user-manager' ),
		'oldest'    => esc_html__( 'Oldest users first', 'wp-user-manager' ),
		'name'      => esc_html__( 'First name', 'wp-user-manager' ),
		'last_name' => esc_html__( 'Last Name', 'wp-user-manager' ),
	);

	return apply_filters( 'wpum_get_directory_sort_by_methods', $options );

}

/**
 * Retrieve a list of options for the per page amount modifier for the directory.
 *
 * @return array
 */
function wpum_get_directory_amount_modifier() {

	$amounts = array(
		''   => '',
		'1'  => '1',
		'10' => '10',
		'15' => '15',
		'20' => '20',
	);

	return apply_filters( 'wpum_get_directory_amount_modifier', $amounts );

}

/**
 * Defines the list of available directory templates.
 *
 * @return array
 */
function wpum_get_directory_templates() {

	$templates = array(
		'default' => esc_html__( 'Default template', 'wp-user-manager' ),
	);

	return apply_filters( 'wpum_get_directory_templates', $templates );

}

/**
 * Defines the list of available directory user templates.
 *
 * @return array
 */
function wpum_get_directory_user_templates() {

	$templates = array(
		'default' => esc_html__( 'Default template', 'wp-user-manager' ),
	);

	return apply_filters( 'wpum_get_directory_user_templates', $templates );

}

/**
 * Display pagination for user directory.
 *
 * @param object $data
 * @return void
 */
function wpum_user_directory_pagination( $data ) {

	echo '<div class="wpum-directory-pagination">';

	$big          = 9999999;
	$search_for   = array( $big, '#038;' );
	$replace_with = array( '%#%', '&' );

	echo wp_kses_post( paginate_links( array(
		'base'      => str_replace( $search_for, $replace_with, esc_url( get_pagenum_link( $big ) ) ),
		'current'   => $data->paged,
		'total'     => $data->total_pages,
		'prev_text' => __( 'Previous page', 'wp-user-manager' ),
		'next_text' => __( 'Next page', 'wp-user-manager' ),
	) ) );

	echo '</div>';

}
