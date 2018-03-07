<?php
/**
 * Handles all the rest api related functionalities of WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

/**
 * The class that handles the WPUM API.
 */
class WPUM_Rest extends WP_REST_Controller {

	/**
	 * Namespace of the api.
	 *
	 * @var string
	 */
	public $namespace = 'wpum/';

	/**
	 * Version of this api.
	 *
	 * @var string
	 */
	public $version   = 'v1';

	/**
	 * Initialize the api.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_js_variables' ] );
	}

	/**
	 * Add local js variables to the frontend.
	 *
	 * @return void
	 */
	public function register_js_variables() {

		$rest_url = get_rest_url( null, $this->namespace . $this->version );
		$nonce    = wp_create_nonce( 'wpum_rest' );

		$js_variables = [
			'rest'      => $rest_url,
			'nonce'     => $nonce,
			'html_tags' => wpum_get_vuejs_allowed_tags()
		];

		wp_localize_script( 'login.js', 'wpumRest', $js_variables );

	}

	/**
	 * Register routes for this api.
	 *
	 * @return void
	 */
	public function register_routes() {

		$namespace = $this->namespace . $this->version;

		register_rest_route( $namespace, '/get-form/(?P<form>(.*)+)', array(
			array(
				'methods'  => WP_REST_Server::READABLE,
				'callback' => [ $this, 'get_form' ],
				'args' => [
					'form' => [
						'sanitize_callback' => function( $param ) {
							return $this->form_exists( $param );
						}
					]
				]
			),
		) );

	}

	/**
	 * Check if the form exists before making the actual request.
	 *
	 * @param string $form_id
	 * @return void
	 */
	private function form_exists( $form_id ) {
		$form_id = sanitize_text_field( $form_id );
		return WPUM()->forms->get_form( $form_id );
	}

	/**
	 * Retrieve the form from the backend.
	 *
	 * @param WP_REST_Request $request
	 * @return object
	 */
	public function get_form( WP_REST_Request $request ) {

		check_ajax_referer( 'wpum_rest', 'nonce' );

		$params = $request->get_params();

		if ( ! isset( $params['form'] ) || empty( $params['form'] ) ) {
			return new WP_Error( 'no-param', __( 'The form requested was not found.' ) );
		}

		$response = [
			'form' => $params['form'],
		];

		return rest_ensure_response( $response );

	}

}
