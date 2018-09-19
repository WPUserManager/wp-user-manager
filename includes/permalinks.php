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

use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\QueryRoute;

/**
 * Register custom routing for the account page.
 * The routing will then decide which content to be displayed within that page.
 */
add_action(
	'cortex.routes', function( RouteCollectionInterface $routes ) {

		$account_page_id = wpum_get_core_page_id( 'account' );

		if ( ! $account_page_id ) {
			return;
		}

		$page_slug       = esc_attr( get_post_field( 'post_name', intval( $account_page_id ) ) );
		$hierarchy       = wpum_get_full_page_hierarchy( $account_page_id );

		if ( ! empty( $hierarchy ) && is_array( $hierarchy ) ) {
			$page_slug = '';
			foreach ( array_reverse( $hierarchy )  as $page ) {
				$parent_page_slug = esc_attr( get_post_field( 'post_name', intval( $page['id'] ) ) );
				$page_slug       .= $parent_page_slug . '/';
			}
		}

		$routes->addRoute(
			new QueryRoute(
				$page_slug . '{tab:[a-zA-Z0-9_.-]+}',
				function( array $matches ) use ( $account_page_id ) {
					return [
						'tab'     => $matches['tab'],
						'page_id' => $account_page_id,
					];
				}
			)
		);
	}
);

/**
 * Register rewrite rules for the profile page.
 */
add_action(
	'cortex.routes', function( RouteCollectionInterface $routes ) {

		$profile_page_id = wpum_get_core_page_id( 'profile' );

		if ( ! $profile_page_id ) {
			return;
		}

		$page_slug       = esc_attr( get_post_field( 'post_name', intval( $profile_page_id ) ) );
		$hierarchy       = wpum_get_full_page_hierarchy( $profile_page_id );

		if ( ! empty( $hierarchy ) && is_array( $hierarchy ) ) {
			$page_slug = '';
			foreach ( array_reverse( $hierarchy ) as $page ) {
				$parent_page_slug = esc_attr( get_post_field( 'post_name', intval( $page['id'] ) ) );
				$page_slug       .= $parent_page_slug . '/';
			}
		}

		$routes->addRoute(
			new QueryRoute(
				$page_slug . '{profile:[\w,.!?+%]+}',
				function( array $matches ) use ( $profile_page_id ) {
					return [
						'profile' => $matches['profile'],
						'page_id' => $profile_page_id,
					];
				}
			)
		);

		$routes->addRoute(
			new QueryRoute(
				$page_slug . '{profile:[\w,.!?+%]+}/{tab:[a-zA-Z0-9_.-]+}',
				function( array $matches ) use ( $profile_page_id ) {
					return [
						'profile' => $matches['profile'],
						'tab'     => $matches['tab'],
						'page_id' => $profile_page_id,
					];
				}
			)
		);

		$routes->addRoute(
			new QueryRoute(
				$page_slug . '{profile:[\w,.!?+%]+}/{tab:[a-zA-Z0-9_.-]+}/page/{paged:[a-zA-Z0-9_.-]+}',
				function( array $matches ) use ( $profile_page_id ) {
					return [
						'profile' => $matches['profile'],
						'tab'     => $matches['tab'],
						'paged'   => $matches['paged'],
						'page_id' => $profile_page_id,
					];
				}
			)
		);

	}
);
