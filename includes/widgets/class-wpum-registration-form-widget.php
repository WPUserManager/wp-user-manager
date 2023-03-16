<?php
/**
 * Registration Form Widget.
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
 * WPUM_Registration_Form_Widget Class
 *
 * @since 1.0.0
 */
class WPUM_Registration_Form_Widget extends \WPUM\WPH_Widget {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Configure widget array
		$args = array(
			'label'       => __( '[WPUM] Registration Form', 'wp-user-manager' ),
			'description' => __( 'Display the registration form.', 'wp-user-manager' ),
		);

		$args['fields'] = array(
			array(
				'name'   => __( 'Title', 'wp-user-manager' ),
				'id'     => 'title',
				'type'   => 'text',
				'class'  => 'widefat',
				'std'    => __( 'Register', 'wp-user-manager' ),
				'filter' => 'strip_tags|esc_attr',
			),
			array(
				'name'   => __( 'Display login link', 'wp-user-manager' ),
				'id'     => 'login_link',
				'type'   => 'checkbox',
				'std'    => 1,
				'filter' => 'strip_tags|esc_attr',
			),
			array(
				'name'   => __( 'Display password recovery link', 'wp-user-manager' ),
				'id'     => 'psw_link',
				'type'   => 'checkbox',
				'std'    => 1,
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

		echo do_shortcode( '[wpum_register psw_link="' . ( ( $instance['psw_link'] ) ? 'yes' : 'no' ) . '" login_link="' . ( ( $instance['login_link'] ) ? 'yes' : 'no' ) . '"]' );

		$output = ob_get_clean();

		echo $output;  // phpcs:ignore

		echo wp_kses_post( $args['after_widget'] );
	}

}
