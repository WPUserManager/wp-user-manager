<?php
/**
 * Background Process
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle DB
 * updates in the background.
 *
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM_Async_Process Class.
 */
class WPUM_Async_Process extends WP_Async_Request {

	/**
	 * Prefix
	 *
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'wpum';

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	protected function handle() {
		$_post = $_POST; // phpcs:ignore

		if ( empty( $_post ) || empty( $_post['data'] ) || empty( $_post['hook'] ) ) {
			exit();
		}

		/**
		 * Fire the hook.
		 */
		do_action( $_post['hook'], $_post['data'] );

		exit();
	}
}
