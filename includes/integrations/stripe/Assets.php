<?php

namespace WPUserManager\Stripe;

class Assets {

	/**
	 * @var string
	 */
	protected $public_key;
	/**
	 * Assets constructor.
	 *
	 * @param string $public_key
	 */
	public function __construct( $public_key ) {
		$this->public_key = $public_key;
	}

	public function init() {
		add_action( 'wpum_enqueue_frontend_scripts', array( $this, 'load_scripts' ) );
	}

	public function load_scripts( $suffix ) {
		wp_enqueue_script( 'wpum-stripe-js', 'https://js.stripe.com/v3/', array(), false, false );
		wp_enqueue_script( 'wpum-stripe-frontend-js', WPUM_PLUGIN_URL . 'assets/js/wpum-stripe' . $suffix . '.js', array(
			'jquery',
			'wpum-stripe-js',
		), true, WPUM_VERSION );

		wp_localize_script( 'wpum-stripe-frontend-js', 'wpum_stripe', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'stripe'  => $this->public_key,
		) );
	}
}
