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
		if( defined( 'DOING_AJAX' ) || ( isset( $_GET['wpum_email_customizer'] ) && 'true' == $_GET['wpum_email_customizer'] ) ) {
			add_action( 'customize_register', [ $this, 'customize_register' ], 11 );
			add_filter( 'customize_section_active', [ $this, 'remove_sections' ], 10, 2 );
			add_filter( 'customize_panel_active', [ $this, 'remove_panels' ], 10, 2 );
			add_action( 'parse_request', [ $this, 'customizer_setup_preview' ] );
		}
	}

	/**
	 * Require other classes and functions for the customizer to work.
	 *
	 * @return void
	 */
	private function includes() {
		require_once WPUM_PLUGIN_DIR . 'includes/emails/class-wpum-emails-customizer-editor-control.php';
	}

	/**
	 * Remove all other registered sections except ours.
	 *
	 * @param boolean $active
	 * @param object $section
	 * @return void
	 */
	public function remove_sections( $active, $section ) {
		// Bail if not our customizer.
		if( ! isset( $_GET['wpum_email_customizer'] ) ) {
			return true;
		}
		// Deactivate all other sections except the ones registered for emails.
		if( isset( $_GET['wpum_email_customizer'] ) && $_GET['wpum_email_customizer'] == 'true' ) {
			$sections = [];
			foreach( $this->emails as $email_id => $registered_email ) {
				$sections[] = $email_id . '_settings';
			}
			if( in_array( $section->id, $sections ) ) {
				return true;
			}
			return false;
		}
	}

	/**
	 * Hide all other panels except the ones registered for our customizer.
	 *
	 * @param boolean $active
	 * @param object $panel
	 * @return void
	 */
	public function remove_panels( $active, $panel ) {
		if( ! isset( $_GET['wpum_email_customizer'] ) ) {
			return true;
		}
		// Deactivate all other panels except the ones registered for emails.
		if( isset( $_GET['wpum_email_customizer'] ) && $_GET['wpum_email_customizer'] == 'true' ) {
			$panels = [];
			foreach( $this->emails as $email_id => $registered_email ) {
				$panels[] = $email_id;
			}
			if( in_array( $panel->id, $panels ) && $_GET['email'] == $panel->id ) {
				return true;
			}
			return false;
		}
	}

	/**
	 * Register all the sections for our customizer.
	 *
	 * @param object $wp_customize
	 * @return void
	 */
	public function customize_register( $wp_customize ) {

		$this->includes();

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
					'title'       => esc_html__( 'Email content settings' ),
					'description' => '<a href="#" class="button" id="wpum-display-tags-btn"><span class="dashicons dashicons-editor-code"></span>' . esc_html__( 'View available email merge tags' ) . '</a><div class="wpum-email-tags-list">' . esc_html__( 'Available email merge tags:' ) . '<br/>' . wpum_get_emails_tags_list() . '<hr/></div>',
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
			'default'           => $this->get_default( $email_id, 'title' ),
			'type'              => 'option',
		) );

		$wp_customize->add_control( 'wpum_email[' . $email_id . '][title]', array(
			'type'        => 'text',
			'section'     => $email_id . '_settings',
			'label'       => esc_html__( 'Email heading title' ),
			'description' => esc_html__( 'Customize the heading title of the email.' ),
		) );

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][content]', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'postMessage',
			'type'              => 'option',
		) );

		$wp_customize->add_control( new WPUM_Emails_Customizer_Editor_Control( $wp_customize, 'wpum_email[' . $email_id . '][content]', array(
			'label'       => esc_html__( 'Email content' ),
			'description' => esc_html__( 'Click the button to open the content customization editor.' ),
			'section'     => $email_id . '_settings',
		) ) );

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][footer]', array(
			'capability'        => 'manage_options',
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'postMessage',
			'default'           => '<a href="{siteurl}">{sitename}</a><br />Some content for the footer goes here.',
			'type'              => 'option',
		) );

		$wp_customize->add_control( 'wpum_email[' . $email_id . '][footer]', array(
			'type'        => 'textarea',
			'section'     => $email_id . '_settings',
			'label'       => __( 'Footer tagline' ),
			'description' => esc_html__( 'Customize the footer tagline for this email.' ),
		) );

	}

	/**
	 * Retrieve a defaul value for the registered settings of each email.
	 *
	 * @param string $email_id
	 * @param mixed|boolean $field
	 * @return void
	 */
	private function get_default( $email_id, $field = false ) {

		$default = false;

		$defaults = apply_filters( 'wpum_email_customizer_settings_defaults', [
			'registration_confirmation_title' => esc_html__( 'Welcome to {sitename}!' ),
		] );

		if( $email_id && $field ) {
			$key     = esc_html( "{$email_id}_{$field}" );
			$default = isset( $defaults[ $key ] ) ? $defaults[ $key ] : false;
		}

		return $default;

	}

	/**
	 * Load the email template into the customizer window.
	 *
	 * @return void
	 */
	public function customizer_setup_preview() {

		if( is_customize_preview() && isset( $_GET['email'] ) ) {
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
