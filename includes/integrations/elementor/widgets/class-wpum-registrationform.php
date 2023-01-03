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
	protected $shortcode = 'wpum_register';

	/**
	 * @var string
	 */
	protected $icon = 'eicon-plus-square-o';

	/**
	 * @var array
	 */
	protected $keywords = array(
		'register',
		'user register',
		'registration',
		'user registration',
		'registration form',
	);

	/**
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Registration form', 'wp-user-manager' );
	}

	/**
	 * WPUM Widget Controls
	 */
	public function widget_controls() {
		$controls = array(
			array(
				'id'         => 'login_link',
				'attributes' => array(
					'label'        => esc_html__( 'Show login link', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
			array(
				'id'         => 'psw_link',
				'attributes' => array(
					'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
					'type'         => \Elementor\Controls_Manager::SWITCHER,
					'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
					'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				),
			),
		);

		if ( class_exists( 'WPUM_Registration_Forms' ) ) {
			$controls[] = array(
				'id'         => 'form_id',
				'attributes' => array(
					'label'   => esc_html__( 'Select Registration Form', 'wp-user-manager' ),
					'type'    => \Elementor\Controls_Manager::SELECT,
					'default' => 1,
					'options' => $this->get_registration_forms(),
				),
			);
		}

		return $controls;
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
