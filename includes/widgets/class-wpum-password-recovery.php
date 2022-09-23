<?php
/**
 * Password Recovery Form Widget.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2015, Alessandro Tesoro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM_Password_Recovery Class
 *
 * @since 1.0.0
 */
class WPUM_Password_Recovery extends \WPUM\WPH_Widget {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Configure widget array
		$args = array(
			'label'       => __( '[WPUM] Password Recovery Form', 'wp-user-manager' ),
			'description' => __( 'Display a form for users to recover their password.', 'wp-user-manager' ),
		);

		$args['fields'] = array(
			array(
				'name'   => __( 'Title', 'wp-user-manager' ),
				'id'     => 'title',
				'type'   => 'text',
				'class'  => 'widefat',
				'std'    => __( 'Reset password', 'wp-user-manager' ),
				'filter' => 'strip_tags|esc_attr',
			),
		);

		// create widget
		$this->create_widget( $args );

	}

	/**
	 * Display widget content.
	 *
	 * @access private
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		echo wp_kses_post( $args['before_widget'] );
		echo wp_kses_post( $args['before_title'] );
		echo esc_html( $instance['title'] );
		echo wp_kses_post( $args['after_title'] );

		ob_start();
		echo do_shortcode( '[wpum_password_recovery]' );
		$output = ob_get_clean();

		echo $output;  // phpcs:ignore

		echo wp_kses_post( $args['after_widget'] );
	}

}
