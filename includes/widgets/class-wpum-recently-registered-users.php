<?php
/**
 * Recently registered users widget.
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
 * WPUM_Recently_Registered_Users Class
 *
 * @since 1.0.0
 */
class WPUM_Recently_Registered_Users extends \WPUM\WPH_Widget {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {

		// Configure widget array
		$args = array(
			'label'       => __( '[WPUM] Recently Registered Users', 'wp-user-manager' ),
			'description' => __( 'Display a list of recently registered users.', 'wp-user-manager' ),
		);

		$args['fields'] = array(
			array(
				'name'   => __( 'Title', 'wp-user-manager' ),
				'id'     => 'title',
				'type'   => 'text',
				'class'  => 'widefat',
				'std'    => __( 'Recent Users', 'wp-user-manager' ),
				'filter' => 'strip_tags|esc_attr',
			),
			array(
				'name'     => __( 'Amount', 'wp-user-manager' ),
				'desc'     => __( 'Enter the amount of users you wish to display.', 'wp-user-manager' ),
				'id'       => 'amount',
				'type'     => 'text',
				'class'    => 'widefat',
				'std'      => '10',
				'filter'   => 'strip_tags|esc_attr',
				'validate' => 'numeric',
			),
			array(
				'name'   => __( 'Link to user profile', 'wp-user-manager' ),
				'desc'   => __( 'Enable to link to the user profile.', 'wp-user-manager' ),
				'id'     => 'profile',
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

		if ( 1 === (int) $instance['profile'] ) {
			$instance['profile'] = 'yes';
		}

		ob_start();

		WPUM()->templates
			->set_template_data( array(
				'amount'          => $instance['amount'],
				'link_to_profile' => $instance['profile'],
			) )
			->get_template_part( 'recently-registered' );

		$output = ob_get_clean();

		echo wp_kses_post( $output );

		echo wp_kses_post( $args['after_widget'] );

	}

}
