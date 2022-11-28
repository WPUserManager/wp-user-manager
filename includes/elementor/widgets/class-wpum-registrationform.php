<?php
/**
 * Handles the display of registraton form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2022 WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * Registration form widget
 */
class WPUM_RegistrationForm extends WPUM_Elementor_Widget {

	/**
	 * @var string
	 */
	protected $shortcode_function = 'wpum_registration_form';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-plus-square-o';

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Registration form', 'wp-user-manager' );
	}

	/**
	 * @return array
	 */
	public function get_keywords() {
		return array(
			esc_html__( 'register', 'wp-user-manager' ),
			esc_html__( 'user register', 'wp-user-manager' ),
			esc_html__( 'registration', 'wp-user-manager' ),
			esc_html__( 'user registration', 'wp-user-manager' ),
			esc_html__( 'registration form', 'wp-user-manager' ),
		);
	}

	/**
	 * Register
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'wpum_content_section',
			array(
				'label' => esc_html__( 'Settings', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'login_link',
			array(
				'label'        => esc_html__( 'Show login link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'psw_link',
			array(
				'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		if ( class_exists( 'WPUM_Registration_Forms' ) ) {
			$this->add_control(
				'form_id',
				array(
					'label'   => esc_html__( 'Select Registration Form', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 1,
					'options' => $this->get_registration_forms(),
				)
			);
		}

		$this->end_controls_section();
	}

	/**
	 * @return array
	 */
	protected function get_registration_forms() {
		$forms              = WPUM()->registration_forms->get_forms();
		$registration_forms = array();

		foreach ( $forms as $key => $form ) {
			$registration_forms[ $form->id ] = $form->name;
		}

		return $registration_forms;
	}

	/**
	 * Render
	 */
	public function render() {
		parent::render();

		// Enqueue JS scripts
		wpum_enqueue_scripts();
	}
}
