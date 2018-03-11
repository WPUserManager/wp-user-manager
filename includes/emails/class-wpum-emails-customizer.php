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
	 * Holds the panel ID
	 *
	 * @var string
	 */
	protected $panel_id;

	/**
	 * Holds the settings section id.
	 *
	 * @var string
	 */
	protected $settings_section_id;

	/**
	 * Holds the content section id.
	 *
	 * @var string
	 */
	protected $content_section_id;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->panel_id            = 'wpum_email_editor';
		$this->settings_section_id = 'email_settings';
		$this->content_section_id  = 'email_content';
		$this->init();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	private function init() {
		/*if ( ! isset( $_GET[ WPUM()::CUSTOMIZER_QUERY_PARAM ] ) || 'true' !== wp_unslash( $_GET[ WPUM()::CUSTOMIZER_QUERY_PARAM ] ) ) {
			return;
		}*/
		add_filter( 'customize_loaded_components', [ $this, 'setup_customizer_components' ], 1, 1 );
		add_filter( 'customize_dynamic_setting_args', [ $this, 'register_dynamic_settings' ], 10, 2 );
		add_action( 'customize_controls_init', [ $this, 'persist_email_customizer' ] );
		add_action( 'customize_preview_init', [ $this, 'update_preview' ] );
		add_action( 'parse_request', [ $this, 'customizer_setup_preview' ] );
	}

	/**
	 * Remove all customizer components and load our custom component only
	 * when accessing the customizer through the WPUM emails special url.
	 *
	 * @param array $components
	 * @return void
	 */
	public function setup_customizer_components( $components ) {

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
	 * Persist a specific url parameter so we can understand
	 * if our customizer is currently enabled.
	 *
	 * @return void
	 */
	public function persist_email_customizer() {

		global $wp_customize;

		$wp_customize->set_preview_url(
			add_query_arg(
				array( WPUM()::CUSTOMIZER_QUERY_PARAM => 'true' ),
				$wp_customize->get_preview_url()
			)
		);

	}

	/**
	 * Register our customizer settings.
	 *
	 * @param object $wp_customize
	 * @return void
	 */
	public function customize_register( $wp_customize ) {

		$selected_email_id = $this->get_selected_email();

		$wp_customize->add_panel( $this->panel_id, [
			'title'       => $this->get_email_name(),
			'description' => sprintf(
				esc_html__( 'The WP User Manager email editor allows you to customize the emails sent to your users by WPUM. You\'re currently editing the %s. %s Edit shortcuts are shown for some editable elements of the email.' ),
				'<strong>' . strtolower( $this->get_email_name() ) . '</strong>',
				'<br/><br/>'
			),
			'capability'  => 'manage_options',
		] );

		$wp_customize->add_section( $this->settings_section_id, [
			'title'       => esc_html__( 'Email Settings' ),
			'description' => '',
			'capability'  => 'manage_options',
			'panel'       => $this->panel_id,
		] );

		$wp_customize->add_section( $this->settings_section_id, [
			'title'       => esc_html__( 'Email title and footer' ),
			'description' => '',
			'capability'  => 'manage_options',
			'panel'       => $this->panel_id,
		] );

		$this->register_settings( $wp_customize, $selected_email_id );

	}

	/**
	 * Detect if the customize is active.
	 *
	 * @return boolean
	 */
	private function is_email_customizer_active() {

		$pass = false;

		if( is_customize_preview() && isset( $_GET['wpum_email_customize'] ) && $_GET['wpum_email_customize'] == 'true' ) {
			$pass = true;
		}

		return $pass;
	}

	/**
	 * Retrieve the name of the email based on url parameters.
	 *
	 * @return string
	 */
	private function get_email_name() {

		$name     = 'Unknown';
		$email_id = false;

		if( $this->is_email_customizer_active() ) {
			$email_id = sanitize_text_field( $_GET['email'] );
			switch ($email_id) {
				case 'registration_email':
					$name = esc_html__( 'New account notification email' );
					break;
				case 'password_recovery_email':
					$name = esc_html__( 'Password recovery notification' );
					break;
			}
		}

		return apply_filters( 'wpum_emails_customizer_get_email_name', $name, $email_id );

	}

	/**
	 * Retrieve the selected email into the customizer.
	 *
	 * @return void
	 */
	private function get_selected_email() {

		return isset( $_GET['email'] ) && ! empty( $_GET['email'] ) ? sanitize_text_field( $_GET['email'] ) : false;

	}

	/**
	 * Check if we're viewing the preview through the customizer.
	 *
	 * @return boolean
	 */
	private function is_email_customizer_preview() {
		return isset( $_GET['wpum_email_preview'] ) && $_GET['wpum_email_preview'] && isset( $_GET['email'] ) == 'true' ? true : false;
	}

	/**
	 * Add scripts and styles to the email customizer.
	 *
	 * @return void
	 */
	public function update_preview() {
		wp_enqueue_script( 'wpum-email-customizer', WPUM_PLUGIN_URL . '/assets/js/admin/admin-email-customizer.min.js', array( 'jquery','customize-preview' ) );
	}

	/**
	 * Override the template file loaded within the preview panel.
	 *
	 * @return void
	 */
	public function customizer_setup_preview() {

		if( $this->is_email_customizer_preview() && is_customize_preview() ) {
			WPUM()->templates
				->set_template_data( [
					'email' => sanitize_text_field( $_GET['email'] )
				] )
				->get_template_part( 'email-customizer-preview' );
			exit;
		}

	}

	/**
	 * Register the emails available for customizations through the customizer.
	 *
	 * @return void
	 */
	private function get_registered_settings_emails() {

		$emails = [ 'registration_email', 'password_recovery_email' ];

		return apply_filters( 'wpum_customizer_registered_emails', $emails );

	}

	/**
	 * Dynamically tell the customizer that our dynamic settings exist and are valid.
	 *
	 * @param array $setting_args
	 * @param string $setting_id
	 * @return void
	 */
	public function register_dynamic_settings( $setting_args, $setting_id ) {

		$registered_emails = $this->get_registered_settings_emails();
		$setting_ids       = apply_filters( 'wpum_email_customizer_dynamic_setting_ids', [] );

		foreach( $registered_emails as $email_id ) {
			$setting_ids[] = 'wpum_email[' . $email_id . '][title]';
			$setting_ids[] = 'wpum_email[' . $email_id . '][footer]';
		}

		foreach( $setting_ids as $dynamic_setting_id ) {
			if ( $dynamic_setting_id === $setting_id ) {
				$setting_args = [
					'type' => 'theme_mod',
				];
			}
		}

		return $setting_args;
	}

	/**
	 * Dynamically register settings for the email being customized.
	 *
	 * @param object $wp_customize
	 * @param string $selected_email_id
	 * @return void
	 */
	private function register_settings( $wp_customize, $selected_email_id ) {

		if( ! $selected_email_id || ! $wp_customize || ! in_array( $selected_email_id, $this->get_registered_settings_emails() ) ) {
			return;
		}

		$wp_customize->add_setting( 'wpum_email[' . $selected_email_id . '][title]', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage'
		) );

		$wp_customize->add_control( 'wpum_email[' . $selected_email_id . '][title]', array(
			'type'        => 'text',
			'section'     => $this->settings_section_id,
			'label'       => __( 'Heading title', 'textdomain' ),
			'description' => esc_html__( 'Customize the heading title of the email.' ),
		) );

		$wp_customize->add_setting( 'wpum_email[' . $selected_email_id . '][footer]', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage'
		) );

		$wp_customize->add_control( 'wpum_email[' . $selected_email_id . '][footer]', array(
			'type'        => 'text',
			'section'     => $this->settings_section_id,
			'label'       => __( 'Footer tagline', 'textdomain' ),
			'description' => esc_html__( 'Customize the footer tagline for this email.' ),
		) );

	}

}

new WPUM_Emails_Customizer;
