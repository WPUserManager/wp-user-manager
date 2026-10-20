<?php
/**
 * Keep WPUM's utility pages out of search results and sitemaps.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2026, WP User Manager
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IDs of the pages that are noindexed and left out of sitemaps.
 *
 * Login, registration, password reset, account (with its tabs) and the
 * profile page. Member profiles on the profile page stay indexable unless
 * guests can't view them, see wpum_is_noindex_request().
 *
 * @return int[]
 */
function wpum_get_noindex_page_ids() {
	$page_ids = array();

	foreach ( array( 'login', 'register', 'password' ) as $core_page ) {
		$page_ids[] = wpum_get_core_page_id( $core_page );
	}

	$account_page_id = wpum_get_core_page_id( 'account' );
	if ( $account_page_id ) {
		$page_ids = array_merge( $page_ids, (array) apply_filters( 'wpum_account_page_ids', array( $account_page_id ), $account_page_id ) );
	}

	$page_ids = array_merge( $page_ids, wpum_get_profile_page_ids() );

	/**
	 * Filter the pages WPUM noindexes and leaves out of sitemaps.
	 *
	 * Return an empty array to turn this off.
	 *
	 * @param int[] $page_ids
	 */
	$page_ids = apply_filters( 'wpum_noindex_pages', array_values( array_unique( array_filter( $page_ids ) ) ) );

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $page_ids ) ) ) );
}

/**
 * IDs of the profile pages.
 *
 * @return int[]
 */
function wpum_get_profile_page_ids() {
	$profile_page_id = wpum_get_core_page_id( 'profile' );

	if ( ! $profile_page_id ) {
		return array();
	}

	return array_map( 'absint', (array) apply_filters( 'wpum_profile_page_ids', array( $profile_page_id ), $profile_page_id ) );
}

/**
 * Should the current request be noindexed?
 *
 * @return bool
 */
function wpum_is_noindex_request() {
	$page_ids = wpum_get_noindex_page_ids();

	if ( empty( $page_ids ) || ! is_page( $page_ids ) ) {
		return false;
	}

	if ( ! in_array( get_queried_object_id(), wpum_get_profile_page_ids(), true ) || ! wpum_get_queried_user() ) {
		return true;
	}

	// A member profile. Search engines crawl as guests, so noindex it when guests can't view it.
	$user_id = wpum_get_queried_user_id();

	return ! $user_id || ! wpum_guests_can_view_profiles( $user_id );
}

/**
 * Add noindex to the robots meta tag.
 *
 * @param array $robots
 *
 * @return array
 */
function wpum_noindex_wp_robots( $robots ) {
	if ( ! wpum_is_noindex_request() ) {
		return $robots;
	}

	return wp_robots_no_robots( $robots );
}
add_filter( 'wp_robots', 'wpum_noindex_wp_robots' );

/**
 * Add noindex to the robots meta for Yoast SEO and Rank Math.
 *
 * Both replace the core wp_robots output with their own.
 *
 * @param array $robots
 *
 * @return array
 */
function wpum_noindex_seo_plugin_robots( $robots ) {
	if ( ! is_array( $robots ) || ! wpum_is_noindex_request() ) {
		return $robots;
	}

	$robots['index'] = 'noindex';

	return $robots;
}
add_filter( 'wpseo_robots_array', 'wpum_noindex_seo_plugin_robots' );
add_filter( 'rank_math/frontend/robots', 'wpum_noindex_seo_plugin_robots' );

/**
 * Leave the noindexed pages out of the core sitemap.
 *
 * @param array  $args
 * @param string $post_type
 *
 * @return array
 */
function wpum_exclude_noindex_pages_from_sitemap( $args, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$page_ids = wpum_get_noindex_page_ids();

	if ( ! empty( $page_ids ) ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), $page_ids );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'wpum_exclude_noindex_pages_from_sitemap', 10, 2 );

/**
 * Leave the noindexed pages out of the Yoast SEO and Rank Math sitemaps.
 *
 * @param array $post_ids
 *
 * @return array
 */
function wpum_exclude_noindex_pages_from_seo_plugin_sitemap( $post_ids ) {
	return array_values( array_unique( array_merge( (array) $post_ids, wpum_get_noindex_page_ids() ) ) );
}
add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', 'wpum_exclude_noindex_pages_from_seo_plugin_sitemap' );
add_filter( 'rank_math/sitemap/posts_to_exclude', 'wpum_exclude_noindex_pages_from_seo_plugin_sitemap' );
