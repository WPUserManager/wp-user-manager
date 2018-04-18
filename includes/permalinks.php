<?php
/**
 * Handles all the routing functionalities of WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register custom routing for the account page.
 * The routing will then decide which content to be displayed within that page.
 */
use Brain\Cortex\Route\RouteCollectionInterface;
use Brain\Cortex\Route\QueryRoute;
add_action('cortex.routes', function( RouteCollectionInterface $routes ) {

	$account_page_id   = wpum_get_core_page_id( 'account' );
	$account_page_slug = esc_attr( get_post_field( 'post_name', intval( $account_page_id ) ) );

	$routes->addRoute( new QueryRoute(
		$account_page_slug . '/{tab:[a-z]+}',
		function(array $matches) use( $account_page_id ) {
			return [
		    	'tab'  => $matches['tab'],
				'page_id' => $account_page_id,
		  	];
		}
	) );
} );
