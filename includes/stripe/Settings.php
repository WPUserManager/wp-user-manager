<?php


namespace WPUserManager\WPUMStripe;


class Settings {

	/**
	 * @var Connect
	 */
	protected $connect;

	/**
	 * @param $connect
	 */
	public function __construct( $connect ) {
		$this->connect = $connect;
	}

	public function init() {
		// Add settings to registration form
		add_action( 'wpum_registered_settings', array( $this, 'register_settings' ) );
		add_filter( 'wpum_settings_tabs', array( $this, 'register_setting_tab' ) );
		add_action( 'update_option_wpum_settings', array( $this, 'flush_product_cache' ) );
	}

	/**
	 * Register Stripe settings
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	function register_settings( $settings ) {
		$prefix = $this->connect->is_test_mode() ? 'test' : 'live';

		// TODO handle disconnect


		// TODO make setting HTML not button

		$settings['stripe'][] = array(
			'id'    => 'stripe_connect',
			'name'  =>  __( 'Connect to Stripe', 'wp-user-manager' ),
			'desc'  => __( 'Connect to your Stripe account to get started', 'wp-user-manager' ),
			'type'  => 'html',
			'html'  => sprintf( '<a href="%s"><img src="%s" style="max-width: 160px;"></a>',$this->connect->connect_url(), WPUM_PLUGIN_URL . 'assets/images/stripe-connect.png' ),
			'std'   => 1,
			'toggle' => array(
				array( 'key' => $prefix . '_stripe_publishable_key', 'value' => '' ),
				array( 'key' => $prefix . '_stripe_secret_key', 'value' => '' ),
			),
		);

		// TODO show account details with disconnect link
		$settings['stripe'][] = array(
			'id'    => 'stripe_disconnect',
			'name'  =>  __( 'Disconnect from Stripe', 'wp-user-manager' ),
			'desc'  => __( 'Connect to your Stripe account to get started', 'wp-user-manager' ),
			'type'  => 'html',
			'class' => 'button',
			'html'  => 'Account details',
			'std'   => 1,
			'toggle' => array(
				array( 'key' => $prefix . '_stripe_publishable_key', 'value' => '', 'operator' => '==' ),
				array( 'key' => $prefix . '_stripe_secret_key', 'value' => '', 'operator' => '==' ),
			),
		);

		$settings['stripe'][] = array(
			'id'      => 'stripe_test_mode',
			'name'    => __( 'Test Mode', 'wp-user-manager' ),
			'type'    => 'checkbox',
		);

		// TODO remove key textboxes
		$settings['stripe'][] = array(
			'id'     => 'test_stripe_publishable_key',
			'name'   => __( 'Test Key', 'wp-user-manager' ),
			'type'   => 'text',
			'toggle' => array( 'key' => 'stripe_test_mode', 'value' => true ),
		);
		$settings['stripe'][] = array(
			'id'     => 'test_stripe_secret_key',
			'name'   => __( 'Test Secret', 'wp-user-manager' ),
			'type'   => 'text',
			'toggle' => array( 'key' => 'stripe_test_mode', 'value' => true ),
		);
		$settings['stripe'][] = array(
			'id'     => 'live_stripe_publishable_key',
			'name'   => __( 'Live Key', 'wp-user-manager' ),
			'type'   => 'text',
			'toggle' => array( 'key' => 'stripe_test_mode', 'value' => false ),
		);
		$settings['stripe'][] = array(
			'id'     => 'live_stripe_secret_key',
			'name'   => __( 'Live Secret', 'wp-user-manager' ),
			'type'   => 'text',
			'toggle' => array( 'key' => 'stripe_test_mode', 'value' => false ),
		);

		// TODO add webhook help text and link to doc
		$settings['stripe'][] = array(
			'id'     => 'test_stripe_webhook_secret',
			'name'   => __( 'Test Webhook Signing Secret', 'wp-user-manager' ),
			'type'   => 'text',
			'desc' => 'Set up a webhook in Stripe to get the webhook signing secret, using all events for this URL:<br>' . WebhookEndpoint::get_webhook_url(),
			'toggle' => array( array( 'key' => 'stripe_test_mode', 'value' => true ), array( 'key' => 'test_stripe_secret_key', 'value' => '', 'operator' => '==' ) ),
		);

		$settings['stripe'][] = array(
			'id'     => 'live_stripe_webhook_secret',
			'name'   => __( 'Live Webhook Signing Secret', 'wp-user-manager' ),
			'type'   => 'text',
			'desc' => 'Set up a webhook in Stripe to get the webhook signing secret, using all events for this URL:<br>' . WebhookEndpoint::get_webhook_url(),

			'toggle' => array( array( 'key' => 'stripe_test_mode', 'value' => false ), array( 'key' => 'live_stripe_secret_key', 'value' => '', 'operator' => '==' )),
		);

		$settings['stripe'][] = array(
			'id'   => 'stripe_connect_account_id',
			'name' => __( 'Stripe ID', 'wp-user-manager' ),
			'type' => 'hidden',
		);

		return $settings;
	}

	public function register_setting_tab( $tabs ) {
		$tabs['stripe'] = __( 'Stripe', 'wp-user-manager' );

		return $tabs;
	}

	public function flush_product_cache() {
		delete_transient( 'wpum_stripe_products' );
	}
}
