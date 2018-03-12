<?php
/**
 * Handles the email customizer functionalities in the admin panel.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that handles all the customizer settings.
 */
class WPUM_Emails_Customizer {


	/**
	 * Holds all currently registered emails.
	 *
	 * @var array
	 */
	protected $emails;

	/**
	 * Holds the panel ID.
	 *
	 * @var string
	 */
	protected $panel_id;

	/**
	 * Setup the settings section id used across our customizer.
	 *
	 * @var string
	 */
	public $settings_section_id;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->emails              = wpum_get_registered_emails();
		$this->panel_id            = 'wpum_email_customization';
		$this->settings_section_id = 'wpum_email_settings';
		$this->init();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	private function init() {
		add_filter( 'customize_loaded_components', [ $this, 'detect_email_customizer' ], 1, 1 );
	}

	/**
	 * Detect if our customizer is triggered and hide all other loaded components,
	 * then load our customizer settings only.
	 *
	 * @param array $components
	 * @return void
	 */
	public function detect_email_customizer( $components ) {

		$priority = 1;

		add_action( 'wp_loaded', function() {

			global $wp_customize;

			remove_all_actions( 'customize_register' );

			add_action( 'customize_register', [ $this, 'customize_register' ], 11 );

		}, $priority );

		// Short-circuit widgets, nav-menus, etc from being loaded.
		$components = array();

		return $components;

	}

	/**
	 * Register all the sections for our customizer.
	 *
	 * @param object $wp_customize
	 * @return void
	 */
	public function customize_register( $wp_customize ) {

		foreach( $this->emails as $email_id => $registered_email ) {

			if( isset( $registered_email['name'] ) && isset( $registered_email['description'] ) ) {

				$email_id = esc_attr( $email_id );

				$wp_customize->add_panel( $email_id, [
					'title'       => esc_html( $registered_email['name'] ),
					'description' => sprintf(
						esc_html__( 'The WP User Manager email editor allows you to customize the emails sent to your users by WPUM. You\'re currently editing the %s. %s %s Edit shortcuts are shown for some editable elements of the email.' ),
						'<strong>' . strtolower( $registered_email['name'] ) . '</strong>',
						'<br/><br/>',
						$registered_email['description'] . '<br/><br/>'
					),
					'capability'  => 'manage_options',
				] );

				$wp_customize->add_section( $email_id . '_settings' , [
					'title'       => esc_html__( 'Email settings' ),
					'description' => '',
					'capability'  => 'manage_options',
					'panel'       => $email_id,
				] );

				$this->register_settings( $wp_customize, $email_id );

			}

		}

	}

	/**
	 * Register individual settings for each registered email into the customizer.
	 * Currently registering: heading title, footer tagline.
	 *
	 * @param object $wp_customize
	 * @param string $email_id
	 * @return void
	 */
	private function register_settings( $wp_customize, $email_id = false ) {

		if( ! $wp_customize || ! $email_id ) {
			return;
		}

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][title]', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
			'type'              => 'option',
		) );

		$wp_customize->add_control( 'wpum_email[' . $email_id . '][title]', array(
			'type'        => 'text',
			'section'     => $email_id . '_settings',
			'label'       => __( 'Email heading title' ),
			'description' => esc_html__( 'Customize the heading title of the email.' ),
		) );

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][footer]', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'wp_kses',
			'transport'         => 'postMessage',
			'type'              => 'option',
		) );

		$wp_customize->add_control( 'wpum_email[' . $email_id . '][footer]', array(
			'type'        => 'textarea',
			'section'     => $email_id . '_settings',
			'label'       => __( 'Footer tagline' ),
			'description' => esc_html__( 'Customize the footer tagline for this email.' ),
		) );

	}

}

new WPUM_Emails_Customizer;
