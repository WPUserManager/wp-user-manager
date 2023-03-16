<?php
/**
 * Handles the Stripe webhooks
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2023, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

namespace WPUserManager\Stripe;

/**
 * Webhook Endpoint
 */
class WebhookEndpoint {

	/**
	 * @var StripeWebhookController
	 */
	protected $webhook_controller;

	/**
	 * @var string
	 */
	protected static $namespace = 'wpum/v1';

	/**
	 * @var string
	 */
	protected static $route = '/stripe';

	/**
	 * Registration constructor.
	 *
	 * @param StripeWebhookController $webhook_controller
	 */
	public function __construct( $webhook_controller ) {
		$this->webhook_controller = $webhook_controller;
	}

	/**
	 * Init
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_webhook' ) );
	}

	/**
	 * @return string
	 */
	public static function get_webhook_url() {
		return get_rest_url( null, self::$namespace . self::$route );
	}

	/**
	 * Register enpoint
	 */
	public function register_webhook() {
		register_rest_route( self::$namespace, self::$route, array(
			'methods'             => 'POST',
			'callback'            => array( $this->webhook_controller, 'handleWebhook' ),
			'permission_callback' => '__return_true',
		) );
	}

}
