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
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPUM_Registration_Form_Widget Class
 *
 * @since 1.0.0
 */
class WPUM_Registration_Form_Widget extends WPH_Widget {

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
				'filter' => 'strip_tags|esc_attr'
			),
			array(
				'name'     => __( 'Display login link', 'wp-user-manager' ),
				'id'       => 'login_link',
				'type'     =>'checkbox',
				'std'      => 1,
				'filter'   => 'strip_tags|esc_attr',
			),
			array(
				'name'     => __( 'Display password recovery link', 'wp-user-manager' ),
				'id'       => 'psw_link',
				'type'     =>'checkbox',
				'std'      => 1,
				'filter'   => 'strip_tags|esc_attr',
			)
		);

		// create widget
		$this->create_widget( $args );

	}

	/**
	 * Display widget content.
	 *
	 * @access private
	 * @since 1.0.0
	 * @return void
	 */
	public function widget( $args, $instance ) {

		ob_start();

		echo $args['before_widget'];
		echo $args['before_title'];
		echo $instance['title'];
		echo $args['after_title'];

		$psw_link   = $instance['psw_link'] ? 'yes':   false;
		$login_link = $instance['login_link'] ? 'yes': false;

		echo do_shortcode( '[wpum_register psw_link="'.$psw_link.'" login_link="'.$login_link.'"]' );

		echo $args['after_widget'];

		$output = ob_get_clean();

		echo $output;

	}

}
