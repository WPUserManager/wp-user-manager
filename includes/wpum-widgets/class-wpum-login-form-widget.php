<?php
/**
 * Display login form.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2015, Alessandro Tesoro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPUM_Login_Form_Widget Class
 *
 * @since 1.0.0
 */
class WPUM_Login_Form_Widget extends WPH_Widget {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Configure widget array
		$args = array(
			'label'       => __( '[WPUM] Login Form', 'wp-user-manager' ),
			'description' => __( 'Display login form.', 'wp-user-manager' ),
		);

		$args['fields'] = array(
			array(
				'name'   => __( 'Title', 'wp-user-manager' ),
				'id'     => 'title',
				'type'   => 'text',
				'class'  => 'widefat',
				'std'    => __( 'Login', 'wp-user-manager' ),
				'filter' => 'strip_tags|esc_attr'
			),
			array(
				'name'   => __( 'Logged In title', 'wp-user-manager' ),
				'desc'   => __(' This title will be displayed when logged in.', 'wp-user-manager'),
				'id'     => 'logged_in_title',
				'type'   => 'text',
				'class'  => 'widefat',
				'std'    => __( 'My Account', 'wp-user-manager' ),
				'filter' => 'strip_tags|esc_attr'
			),
			array(
				'name'     => __( 'Display login link', 'wp-user-manager' ),
				'id'       => 'login_link',
				'type'     =>'checkbox',
				'std'      => 0,
				'filter'   => 'strip_tags|esc_attr',
			),
			array(
				'name'     => __( 'Display password recovery link', 'wp-user-manager' ),
				'id'       => 'psw_link',
				'type'     =>'checkbox',
				'std'      => 1,
				'filter'   => 'strip_tags|esc_attr',
			),
			array(
				'name'     => __( 'Display registration link', 'wp-user-manager' ),
				'id'       => 'register_link',
				'type'     =>'checkbox',
				'std'      => 1,
				'filter'   => 'strip_tags|esc_attr',
			),
			array(
				'name'   => __( 'Display profile overview', 'wp-user-manager' ),
				'desc'   => __('If enabled, once logged in, an overview of the current user profile will appear.', 'wp-user-manager'),
				'id'     => 'current_profile',
				'type'   =>'checkbox',
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
	 * @since 1.0.0
	 * @return void
	 */
	public function widget( $args, $instance ) {

		ob_start();

		echo $args['before_widget'];
		echo $args['before_title'];

		if( is_user_logged_in() ) {
			echo $instance['logged_in_title'];
		} else {
			echo $instance['title'];
		}

		echo $args['after_title'];

		// Default form settings
		$settings = array();

		if( is_user_logged_in() && $instance['current_profile'] ) {

			WPUM()->templates
				->get_template_part( 'user-overview' );

		} else {
			echo do_shortcode( '[wpum_login_form psw_link="yes" register_link="yes"]' );
		}

		echo $args['after_widget'];

		$output = ob_get_clean();

		echo $output;

	}

}
