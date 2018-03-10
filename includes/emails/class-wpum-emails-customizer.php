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
		if ( ! isset( $_GET[ WPUM()::CUSTOMIZER_QUERY_PARAM ] ) || 'true' !== wp_unslash( $_GET[ WPUM()::CUSTOMIZER_QUERY_PARAM ] ) ) {
			return;
		}
		add_filter( 'customize_loaded_components', [ $this, 'setup_customizer_components' ], 1, 1 );
		add_action( 'customize_controls_init', [ $this, 'persist_email_customizer' ] );
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

		$wp_customize->add_panel( $this->panel_id, [
			'title'       => esc_html__( 'WP User Manager Emails' ),
			'description' => '',
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

		$wp_customize->add_setting( 'my_theme_mod_setting', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'sanitize_text_field',
		) );

		$wp_customize->add_control( 'my_theme_mod_setting', array(
			'type'        => 'text',
			'section'     => $this->settings_section_id,
			'label'       => __( 'Heading title', 'textdomain' ),
			'description' => esc_html__( 'Customize the heading title of the email.' ),
		) );

	}

	/**
	 * Detect if the customize is active.
	 *
	 * @return boolean
	 */
	private function is_email_customizer_active() {

		$pass = false;

		if( is_customize_preview() && isset( $_GET['wpum_customize_email'] ) && $_GET['wpum_customize_email'] == 'true' ) {
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

		$name = 'Unknown';

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

		return apply_filter( 'wpum_emails_customizer_get_email_name', $name, $email_id );

	}

	/**
	 *
	 *
	 * @return boolean
	 */
	private function is_email_customizer_preview() {
		return isset( $_GET['wpum_email_preview'] ) && $_GET['wpum_email_preview'] && isset( $_GET['email'] ) == 'true' ? true : false;
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

}

new WPUM_Emails_Customizer;
