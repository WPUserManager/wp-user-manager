<?php
/**
 * Handles the email customizer functionalities in the admin panel.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		if ( defined( 'DOING_AJAX' ) || 'true' === filter_input( INPUT_GET, 'wpum_email_customizer' ) ) {
			add_action( 'customize_register', array( $this, 'customize_register' ), 11 );
			add_filter( 'customize_section_active', array( $this, 'remove_sections' ), 10, 2 );
			add_filter( 'customize_panel_active', array( $this, 'remove_panels' ), 10, 2 );
			add_action( 'parse_request', array( $this, 'customizer_setup_preview' ) );
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
	 * @param object  $section
	 * @return bool
	 */
	public function remove_sections( $active, $section ) {
		$wpum_email_customizer = filter_input( INPUT_GET, 'wpum_email_customizer', FILTER_SANITIZE_STRING );

		// Bail if not our customizer.
		if ( empty( $wpum_email_customizer ) ) {
			return true;
		}

		// Deactivate all other sections except the ones registered for emails.
		if ( 'true' === $wpum_email_customizer ) {
			$sections = array();
			foreach ( wpum_get_registered_emails() as $email_id => $registered_email ) {
				$sections[] = $email_id . '_settings';
			}
			if ( in_array( $section->id, $sections, true ) ) {
				return true;
			}

			return false;
		}

		return true;
	}

	/**
	 * Hide all other panels except the ones registered for our customizer.
	 *
	 * @param bool   $active
	 * @param object $panel
	 *
	 * @return bool
	 */
	public function remove_panels( $active, $panel ) {
		$wpum_email_customizer = filter_input( INPUT_GET, 'wpum_email_customizer', FILTER_SANITIZE_STRING );

		if ( empty( $wpum_email_customizer ) ) {
			return true;
		}

		// Deactivate all other panels except the ones registered for emails.
		if ( 'true' === $wpum_email_customizer ) {
			$panels = array();
			foreach ( wpum_get_registered_emails() as $email_id => $registered_email ) {
				$panels[] = $email_id;
			}
			if ( in_array( $panel->id, $panels, true ) && filter_input( INPUT_GET, 'email', FILTER_SANITIZE_STRING ) === $panel->id ) {
				return true;
			}
			return false;
		}

		return true;
	}

	/**
	 * Register all the sections for our customizer.
	 *
	 * @param object $wp_customize
	 * @return void
	 */
	public function customize_register( $wp_customize ) {

		$this->includes();

		foreach ( wpum_get_registered_emails() as $email_id => $registered_email ) {

			if ( isset( $_GET['email'] ) && $_GET['email'] !== $email_id ) { // phpcs:ignore
				continue;
			}

			if ( isset( $registered_email['name'] ) && isset( $registered_email['description'] ) ) {

				$email_id = esc_attr( $email_id );

				$wp_customize->add_panel( $email_id, array(
					'title'       => esc_html( $registered_email['name'] ),
					'description' => sprintf(
						// translators: %1$s email name %2$s html break %3$s email description
						esc_html__( 'The WP User Manager email editor allows you to customize the emails sent to your users by WPUM. You\'re currently editing the %1$s. %2$s %3$s Edit shortcuts are shown for some editable elements of the email.', 'wp-user-manager' ),
						'<strong>' . strtolower( $registered_email['name'] ) . '</strong>',
						'<br/><br/>',
						$registered_email['description'] . '<br/><br/>'
					),
					'capability'  => apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
				) );

				$wp_customize->add_section( $email_id . '_settings', array(
					'title'       => esc_html__( 'Email content settings', 'wp-user-manager' ),
					'description' => '<a href="#" class="button" id="wpum-display-tags-btn"><span class="dashicons dashicons-editor-code"></span>' . esc_html__( 'View available email merge tags', 'wp-user-manager' ) . '</a><div class="wpum-email-tags-list"><strong>' . esc_html__( 'Available email merge tags:', 'wp-user-manager' ) . '</strong><br/>' . wpum_get_emails_tags_list() . '<hr/></div>',
					'capability'  => apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
					'panel'       => $email_id,
				) );

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
	 *
	 * @return void
	 */
	private function register_settings( $wp_customize, $email_id = false ) {

		if ( ! $wp_customize || ! $email_id ) {
			return;
		}

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][subject]', array(
			'capability'        => apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
			'default'           => $this->get_default( $email_id, 'subject' ),
			'type'              => 'option',
		) );

		$wp_customize->add_control( 'wpum_email[' . $email_id . '][subject]', array(
			'type'        => 'text',
			'section'     => $email_id . '_settings',
			'label'       => esc_html__( 'Email subject', 'wp-user-manager' ),
			'description' => esc_html__( 'Customize the subject line of the email.', 'wp-user-manager' ),
		) );

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][title]', array(
			'capability'        => apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'postMessage',
			'default'           => $this->get_default( $email_id, 'title' ),
			'type'              => 'option',
		) );

		$wp_customize->add_control( 'wpum_email[' . $email_id . '][title]', array(
			'type'        => 'text',
			'section'     => $email_id . '_settings',
			'label'       => esc_html__( 'Email heading title', 'wp-user-manager' ),
			'description' => esc_html__( 'Customize the heading title of the email.', 'wp-user-manager' ),
		) );

		$wp_customize->add_setting( 'wpum_email[' . $email_id . '][content]', array(
			'capability'        => apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
			'sanitize_callback' => 'wp_kses_post',
			'transport'         => 'postMessage',
			'default'           => $this->get_default( $email_id, 'content' ),
			'type'              => 'option',
		) );

		$wp_customize->add_control( new WPUM_Emails_Customizer_Editor_Control( $wp_customize, 'wpum_email[' . $email_id . '][content]', array(
			'label'       => esc_html__( 'Email content', 'wp-user-manager' ),
			'description' => esc_html__( 'Click the button to open the content customization editor.', 'wp-user-manager' ),
			'section'     => $email_id . '_settings',
		) ) );

	}

	/**
	 * Retrieve a defaul value for the registered settings of each email.
	 *
	 * @param string        $email_id
	 * @param mixed|boolean $field
	 *
	 * @return false|mixed
	 */
	private function get_default( $email_id, $field = false ) {

		$default = false;

		$defaults = apply_filters( 'wpum_email_customizer_settings_defaults', array(
			'registration_confirmation_title'   => esc_html__( 'Welcome to {sitename}!', 'wp-user-manager' ),
			'registration_confirmation_subject' => esc_html__( 'Welcome to {sitename}!', 'wp-user-manager' ),
			'registration_confirmation_content' => "<p>Hello {username}, and welcome to {sitename}. We’re thrilled to have you on board.</p>
<p>For reference, here\'s your login information:</p>
<p>Username: {username}<br />Login page: {login_page_url}</p>
<p>Thanks,<br />{sitename}</p>",
			'password_recovery_request_subject' => esc_html__( 'Reset your {sitename} password', 'wp-user-manager' ),
			'password_recovery_request_title'   => esc_html__( 'Reset your {sitename} password', 'wp-user-manager' ),
			'password_recovery_request_content' => '<p>Hello {username},</p>
<p>You are receiving this message because you or somebody else has attempted to reset your password on {sitename}.</p>
<p>If this was a mistake, just ignore this email and nothing will happen.</p>
<p>To reset your password, visit the following address:</p>
<p>{recovery_url}</p>',
		) );

		if ( $email_id && $field ) {
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
		$email = filter_input( INPUT_GET, 'email', FILTER_SANITIZE_STRING );

		if ( is_customize_preview() && $email ) {

			$email_id = sanitize_text_field( $email );

			WPUM()->templates
				->set_template_data( array(
					'email_id' => sanitize_text_field( $email_id ),
					'heading'  => wpum_get_email_field( $email_id, 'title' ),
					'preview'  => true,
				) )
				->get_template_part( 'email-customizer-preview' );
			exit;
		}

	}

}

new WPUM_Emails_Customizer();
