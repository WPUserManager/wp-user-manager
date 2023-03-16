<?php
/**
 * Handles the display of profile card to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Profile car widget
 */
class WPUM_ProfileCard extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode = 'wpum_profile_card';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-call-to-action';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'profile',
		'user',
		'card',
		'profile card',
		'user profile',
	);

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Profile card', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		return array(
			array(
				'id'         => 'user_id',
				'attributes' => array(
					'label'   => esc_html__( 'Select User', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => get_current_user_id(),
					'options' => $this->get_users(),
				),
			),
			array(
				'id'         => 'link_to_profile',
				'attributes' => array(
					'label'        => esc_html__( 'Profile link', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
			array(
				'id'         => 'display_buttons',
				'attributes' => array(
					'label'        => esc_html__( 'Show buttons', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
			array(
				'id'         => 'display_cover',
				'attributes' => array(
					'label'        => esc_html__( 'Display profile cover', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
		);
	}

	/**
	 * Get Users
	 *
	 * @return array
	 */
	public function get_users() {
		$users = array();

		foreach ( get_users() as $user ) {
			$users[ $user->ID ] = $user->user_login;
		}

		$users = apply_filters( 'wpum_users_profile_card', $users );

		return $users;
	}
}
