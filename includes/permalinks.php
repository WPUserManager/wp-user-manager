<?php
/**
 * Handles all the routing functionalities of WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPUM\Brain\Cortex\Route\RouteCollectionInterface;
use WPUM\Brain\Cortex\Route\QueryRoute;

/**
 * Register custom routing for the account page.
 * The routing will then decide which content to be displayed within that page.
 */
add_action(
	'cortex.routes',
	function ( RouteCollectionInterface $routes ) {

		$account_page_id = wpum_get_core_page_id( 'account' );
		$exists          = 'publish' === get_post_status( $account_page_id );

		if ( ! $account_page_id || ! $exists ) {
			return;
		}

		$account_page_ids = array_unique( array_filter( apply_filters( 'wpum_account_page_ids', array( $account_page_id ), $account_page_id ) ) );

		$account_page_slugs = array();

		foreach ( $account_page_ids as $account_page_id ) {
			$page_slug = esc_attr( get_post_field( 'post_name', intval( $account_page_id ) ) );
			$hierarchy = wpum_get_full_page_hierarchy( $account_page_id );

			if ( ! empty( $hierarchy ) && is_array( $hierarchy ) ) {
				$page_slug = '';
				foreach ( array_reverse( $hierarchy ) as $page ) {
					$parent_page_slug = esc_attr( get_post_field( 'post_name', intval( $page['id'] ) ) );

					$page_slug .= $parent_page_slug . '/';
				}
			}

			$page_slug = apply_filters( 'wpum_account_page_slug', $page_slug, $account_page_id );

			if ( in_array( $page_slug, $account_page_slugs, true ) ) {
				continue;
			}

			$account_page_slugs[] = $page_slug;

			$routes->addRoute(
				new QueryRoute(
					$page_slug . '{tab:[^/]+}',
					function ( array $matches ) use ( $account_page_id ) {
						return array(
							'tab'     => rawurldecode( $matches['tab'] ),
							'page_id' => $account_page_id,
						);
					}
				)
			);
		}
	}
);

/**
 * Register rewrite rules for the profile page.
 */
add_action(
	'cortex.routes',
	function ( RouteCollectionInterface $routes ) {

		$profile_page_id = wpum_get_core_page_id( 'profile' );

		if ( ! $profile_page_id ) {
			return;
		}

		$account_page_id = wpum_get_core_page_id( 'account' );

		if ( $account_page_id === $profile_page_id ) {
			return;
		}

		$exists = 'publish' === get_post_status( $profile_page_id );

		if ( ! $exists ) {
			return;
		}

		$profile_page_ids = array_unique( array_filter( apply_filters( 'wpum_profile_page_ids', array( $profile_page_id ), $profile_page_id ) ) );

		$profile_page_slugs = array();
		foreach ( $profile_page_ids as $profile_page_id ) {

			$page_slug = esc_attr( get_post_field( 'post_name', intval( $profile_page_id ) ) );
			$hierarchy = wpum_get_full_page_hierarchy( $profile_page_id );

			if ( ! empty( $hierarchy ) && is_array( $hierarchy ) ) {
				$page_slug = '';
				foreach ( array_reverse( $hierarchy ) as $page ) {
					$parent_page_slug = esc_attr( get_post_field( 'post_name', intval( $page['id'] ) ) );

					$page_slug .= $parent_page_slug . '/';
				}
			}

			$page_slug = apply_filters( 'wpum_profile_page_slug', $page_slug, $profile_page_id );

			if ( in_array( $page_slug, $profile_page_slugs, true ) ) {
				continue;
			}

			$profile_page_slugs[] = $page_slug;

			$routes->addRoute( new QueryRoute( $page_slug . '{profile:[^/]+}', function ( array $matches ) use ( $profile_page_id ) {
				return array(
					'profile' => rawurldecode( $matches['profile'] ),
					'page_id' => $profile_page_id,
				);
			} ) );

			$routes->addRoute( new QueryRoute( $page_slug . '{profile:[^/]+}/{tab:[a-zA-Z0-9_.-]+}', function ( array $matches ) use ( $profile_page_id ) {
				return array(
					'profile' => rawurldecode( $matches['profile'] ),
					'tab'     => rawurldecode( $matches['tab'] ),
					'page_id' => $profile_page_id,
				);
			} ) );

			$routes->addRoute( new QueryRoute( $page_slug . '{profile:[^/]+}/{tab:[a-zA-Z0-9_.-]+}/page/{paged:[a-zA-Z0-9_.-]+}', function ( array $matches ) use ( $profile_page_id ) {
				return array(
					'profile' => rawurldecode( $matches['profile'] ),
					'tab'     => rawurldecode( $matches['tab'] ),
					'paged'   => $matches['paged'],
					'page_id' => $profile_page_id,
				);
			} ) );

		}
	}
);
