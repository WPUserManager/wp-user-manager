<?php
/**
 * Handles the display of registraton form to elementor builder.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

class RegistrationForm extends \Elementor\Widget_Base {

	protected $shortcode_function = 'wpum_registration_form';

	public function get_name() {
		return 'registration-form';
	}

	public function get_title() {
		return esc_html__( 'Registration form', 'wp-user-manager' );
	}

	public function get_icon() {
		return 'eicon-plus-square-o';
	}
	
	public function get_categories() {
		return [ 'wp-user-manager' ];
	}

	public function get_keywords(){
		return [
			esc_html__( 'register', 'wp-user-manager' ),
			esc_html__( 'user register', 'wp-user-manager' ),
			esc_html__( 'registration', 'wp-user-manager' ),
			esc_html__( 'user registration', 'wp-user-manager' ),
			esc_html__( 'registration form', 'wp-user-manager' )
		];
	}

	protected function register_controls() {
		$this->start_controls_section(
			'wpum_content_section',
			[
				'label' => esc_html__( 'Settings', 'wp-user-manager' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'login_link',
			[
				'label'        => esc_html__( 'Show login link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'psw_link',
			[
				'label'        => esc_html__( 'Show password recovery link', 'wp-user-manager' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'wp-user-manager' ),
				'label_off'    => esc_html__( 'No', 'wp-user-manager' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'form_id',
			[
				'label'   => esc_html__( 'Select Registration Form', 'wp-user-manager' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 1,
				'options' => $this->get_registration_forms()
			]
		);

		$this->end_controls_section();
	}

	protected function get_registration_forms() {
		$forms = WPUM()->registration_forms->get_forms();
		$registration_forms = [];
	
		foreach ( $forms as $key => $form ) {
			$registration_forms[ $form->id ] = $form->name;
		}

		return $registration_forms;
	}

	public function render() {
		$attributes = $this->get_settings_for_display();
		echo call_user_func( $this->shortcode_function, $attributes );

		// Enqueue JS scripts
		wpum_enqueue_scripts();
	}
}