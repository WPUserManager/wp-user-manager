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
