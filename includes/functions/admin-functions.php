<?php
/**
 * Functions meant to be used within the administration only.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve pages from the database and cache them as transient.
 *
 * @return array
 */
function wpum_get_pages() {

	$pages     = [];
	$transient =  get_transient( 'wpum_get_pages' );

	if ( $transient ) {
		$pages = $transient;
	} else {
		$available_pages = get_pages();
		if ( ! empty( $available_pages ) ) {
			foreach ( $available_pages as $page ) {
				$pages[] = array(
					'value' => $page->ID,
					'label' => $page->post_title
				);
			}
			set_transient( 'wpum_get_pages', $pages, DAY_IN_SECONDS );
		}
	}
	return $pages;
}

/**
 * Retrieve the options for the available login methods.
 *
 * @return array
 */
function wpum_get_login_methods() {
	return apply_filters( 'wpum_get_login_methods', array(
		'username'       => __( 'Username only', 'wpum' ),
		'email'          => __( 'Email only', 'wpum' ),
		'username_email' => __( 'Username or Email', 'wpum' ),
	) );
}

/**
 * Retrieve a list of all user roles and cache them into a transient.
 *
 * @return array
 */
function wpum_get_roles() {

	$roles = [];
	$transient =  get_transient( 'wpum_get_roles' );

	if ( $transient ) {
		$roles = $transient;
	} else {

		global $wp_roles;
		$available_roles = $wp_roles->get_names();

		foreach ( $available_roles as $role_id => $role ) {
			if( $role_id == 'administrator' ) {
				continue;
			}
			$roles[] = array(
				'value' => esc_attr( $role_id ),
				'label' => esc_html( $role ),
			);
		}
		set_transient( 'wpum_get_roles', $roles, DAY_IN_SECONDS );

	}

	return $roles;

}
