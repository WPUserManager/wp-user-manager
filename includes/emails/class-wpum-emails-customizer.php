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
		add_action( 'admin_init', [ $this, 'redirect_to_customizer' ] );
		add_action( 'customize_register', array( $this, 'customize_register' ), 11 );
	}

	/**
	 * Redirect the user to the email customizer when the browser url contains some parameters.
	 *
	 * @return void
	 */
	public function redirect_to_customizer() {
		if( $this->redirect_detected() ) {
			$url = add_query_arg(
				array(
					'email'                => sanitize_text_field( $_GET['wpum_email'] ),
					'wpum_customize_email' => true,
				),
				admin_url( 'customize.php' )
			);
			wp_safe_redirect( $url );
		}
	}

	/**
	 * Trigger a redirect in the admin panel to the customizer.
	 *
	 * @return boolean
	 */
	private function redirect_detected() {

		$pass = false;

		if(
			isset( $_GET['wpum_email_customizer'] )
			&& $_GET['wpum_email_customizer'] == 'true'
			&& isset( $_GET['wpum_email'] )
			&& ! empty( $_GET['wpum_email'] ) ) {
			$pass = true;
		}

		return $pass;

	}

	/**
	 * Register our customizer settings.
	 *
	 * @param object $wp_customize
	 * @return void
	 */
	public function customize_register( $wp_customize ) {

		$wp_customize->add_panel( $this->panel_id, [
			'title'       => esc_html__( 'WPUM Emails Editor' ),
			'description' => esc_html__( 'Description of what this panel does.' ),
			'capability'  => 'manage_options',
		] );

		$wp_customize->add_section( $this->settings_section_id, [
			'title'       => esc_html__( 'Email Settings' ),
			'description' => '',
			'capability'  => 'manage_options',
			'panel'       => $this->panel_id,
		] );

		$wp_customize->add_section( $this->settings_section_id, [
			'title'       => esc_html__( 'Email Content' ),
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
			'description' => '',
		) );

	}

	private function is_email_customizer_active() {

		$pass = false;

		if(
			is_customize_preview()
			&& isset( $_GET['wpum_customize_email'] )
			&& $_GET['wpum_customize_email'] == 'true'
			&& isset( $_GET['email'] )
			&& ! empty( $_GET['email'] )
		) {
			$pass = true;
		}

		return $pass;
	}

	private function get_email_name() {

		$name = '';

		if( $this->is_email_customizer_active() ) {
			$name = 'User registration confirmation email';
		}

		return $name;

	}

}

new WPUM_Emails_Customizer;
