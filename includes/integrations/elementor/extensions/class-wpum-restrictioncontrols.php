<?php
/**
 * Register elementor restriction controls.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

use Elementor\Controls_Stack;

/**
 * Registrastion Controls extension
 */
class WPUM_RestrictionControls {

	/**
	 * @var WPUM_Elementor_Control
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected $restricted_elementor_widgets = array();

	/**
	 * Get instance.
	 *
	 * @return static
	 * @since
	 * @access static
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Init
	 */
	public function init() {
		$this->restricted_elementor_widgets = apply_filters( 'restricted_elementor_widgets', array(
			'post-form',
			'user-directory',
			'logout-link',
			'group-directory',
			'recently-registered-users',
		) );

		add_action( 'elementor/element/after_section_end', array( $this, 'wpum_restriction_controls' ), 10, 2 );
	}

	/**
	 * Register controls
	 *
	 * @param Controls_Stack $section
	 * @param string         $section_id
	 */
	public function wpum_restriction_controls( $section, $section_id ) {
		if ( 'wpum_content_section' !== $section_id ) {
			return;
		}

		if ( ! in_array( $section->get_name(), $this->restricted_elementor_widgets, true ) ) {
			return;
		}

		$section->start_controls_section(
			'wpum_restriction_section',
			array(
				'label' => __( 'WP User Manager - Restriction', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			),
		);

		$section->add_control(
			'wpum_restrict_type',
			array(
				'label'       => '',
				'label_block' => true,
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => 'wpum_restrict_type_state',
				'options'     => array(
					'wpum_restrict_type_state' => 'Show widget by users state',
					'wpum_restrict_type_role'  => 'Show widget by user role',
					'wpum_restrict_type_user'  => 'Show widget for certain users',
				),
			),
		);

		$section->add_control(
			'wpum_restrict_state',
			array(
				'label'       => __( 'Select the type of users to show to', 'wp-user-manager' ),
				'label_block' => true,
				'type'        => \Elementor\Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''    => 'Show to all users',
					'in'  => 'Show only to logged in users',
					'out' => 'Show only to logged out users',
				),
				'condition'   => array(
					'wpum_restrict_type' => 'wpum_restrict_type_state',
				),
			),
		);

		$section->add_control(
			'wpum_restrict_roles',
			array(
				'label'       => __( 'Display only to users with these roles', 'wp-user-manager' ),
				'label_block' => true,
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_user_roles(),
				'condition'   => array(
					'wpum_restrict_type' => 'wpum_restrict_type_role',
				),
			),
		);

		$section->add_control(
			'wpum_restrict_users',
			array(
				'label'       => __( 'Display only to these users', 'wp-user-manager' ),
				'label_block' => true,
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'options'     => $this->get_users(),
				'condition'   => array(
					'wpum_restrict_type' => 'wpum_restrict_type_user',
				),
			),
		);

		$section->add_control(
			'wpum_restrict_show_message',
			array(
				'label'        => esc_html__( 'Display restricted message', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'no',
			),
		);

		$section->end_controls_section();
	}

	/**
	 * @return array
	 */
	public function get_users() {
		$users = array();

		foreach ( get_users() as $user ) {
			$users[ $user->ID ] = $user->user_login;
		}

		return $users;
	}

	/**
	 * @return array
	 */
	public function get_user_roles() {
		$roles = array();

		foreach ( wpum_get_roles( true, true ) as $role ) {
			$roles[ $role['value'] ] = $role['label'];
		}

		return $roles;
	}
}
