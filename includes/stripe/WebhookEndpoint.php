<?php

namespace WPUserManager\WPUMStripe;

class WebhookEndpoint {

	/**
	 * @var StripeWebhookController
	 */
	protected $webhookController;

	protected static $namespace = 'wpum/v1';
	protected static $route = '/stripe';

	/**
	 * Registration constructor.
	 *
	 * @param StripeWebhookController $webhookController
	 */
	public function __construct( $webhookController ) {
		$this->webhookController = $webhookController;
	}

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_webhook' ) );
	}

	public static function get_webhook_url() {
		return get_rest_url( null, self::$namespace . self::$route );
	}

	public function register_webhook() {
		register_rest_route( self::$namespace, self::$route, array(
			'methods'             => 'POST',
			'callback'            => array( $this->webhookController, 'handleWebhook' ),
			'permission_callback' => '__return_true',
		) );
	}

}
