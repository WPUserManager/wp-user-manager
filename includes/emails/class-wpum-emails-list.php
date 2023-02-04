<?php
/**
 * Handles the email templates list in the backend.
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
 * Class that handles the email customization functionalities.
 */
class WPUM_Emails_List {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_menu', array( $this, 'setup_menu_page' ), 9 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_wpum_send_test_email', array( $this, 'send_test_email' ) );
		add_action( 'wp_ajax_wpum_enabled_email', array( $this, 'wpum_enabled_email' ) );
	}

	/**
	 * Add new menu page to the "Users" menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Emails', 'wp-user-manager' ),
			esc_html__( 'Emails', 'wp-user-manager' ),
			apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
			'wpum-emails',
			array( $this, 'display_emails_list' )
		);
	}

	/**
	 * Add the required scripts to the emails editor page.
	 *
	 * @return void
	 */
	public function load_scripts() {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_STRING );

		if ( 'wpum-emails' === $page ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV;

			// Detect if vue deb mode or not and register the appropriate script url.
			if ( $is_vue_dev ) {
				$vue_dev_port = defined( 'WPUM_VUE_DEV_PORT' ) ? WPUM_VUE_DEV_PORT : '8080';
				wp_register_script( 'wpum-emails-editor', 'http://localhost:' . $vue_dev_port . '/emails.js', array(), WPUM_VERSION, true );
			} else {
				wp_register_script( 'wpum-emails-editor', WPUM_PLUGIN_URL . 'dist/static/js/emails.js', array(), WPUM_VERSION, true );
			}

			if ( ! $is_vue_dev ) {
				wp_enqueue_script( 'wpum-vue-manifest' );
				wp_enqueue_script( 'wpum-vue-vendor' );
				wp_enqueue_style( 'wpum-emails-editor-css', WPUM_PLUGIN_URL . 'dist/static/css/emails.css', array(), WPUM_VERSION );
			}

			wp_enqueue_script( 'wpum-emails-editor' );

			$js_variables = array(
				'ajax'          => admin_url( 'admin-ajax.php' ),
				'customizeurl'  => admin_url( 'customize.php' ),
				'url'           => site_url( '/' ),
				'pluginURL'     => WPUM_PLUGIN_URL,
				'nonce'         => wp_create_nonce( 'wpum_test_email' ),
				'default_email' => get_option( 'admin_email' ),
				'emails'        => wpum_get_registered_emails(),
				'labels'        => array(
					'title'             => esc_html__( 'WP User Manager Emails Customization', 'wp-user-manager' ),
					'email'             => esc_html__( 'Email', 'wp-user-manager' ),
					'description'       => esc_html__( 'Description', 'wp-user-manager' ),
					'recipients'        => esc_html__( 'Recipient(s)', 'wp-user-manager' ),
					'active'            => esc_html__( 'Enabled', 'wp-user-manager' ),
					'tooltip_automatic' => esc_html__( 'Sent automatically', 'wp-user-manager' ),
					'tooltip_manual'    => esc_html__( 'Manually triggered', 'wp-user-manager' ),
					'placeholder'       => esc_html__( 'Enter email address...', 'wp-user-manager' ),
					'customize'         => esc_html__( 'Customize', 'wp-user-manager' ),
					'send'              => esc_html__( 'Send test email', 'wp-user-manager' ),
					'success'           => esc_html__( 'Test email successfully sent.', 'wp-user-manager' ),
					'error'             => esc_html__( 'Something went wrong while sending the test email. Please verify the email address you typed is correct or check your server logs.', 'wp-user-manager' ),
				),
			);

			wp_localize_script( 'wpum-emails-editor', 'wpumEmailsEditor', $js_variables );

		}

	}

	/**
	 * Display the list of available emails.
	 *
	 * @return void
	 */
	public function display_emails_list() {
		echo '<div class="wrap"><div id="wpum-emails-list"></div></div>';
	}

	/**
	 * Send test email via ajax.
	 *
	 * @return void
	 */
	public function send_test_email() {

		check_ajax_referer( 'wpum_test_email', 'nonce' );

		$email = filter_input( INPUT_POST, 'email', FILTER_SANITIZE_EMAIL );

		if ( $email && is_email( $email ) && current_user_can( apply_filters( 'wpum_admin_pages_capability', 'manage_options' ) ) && is_admin() ) {

			$emails = new WPUM_Emails();
			$emails->__set( 'heading', esc_html__( 'Test email', 'wp-user-manager' ) );

			$sitename = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$subject  = sprintf( esc_html( 'Test email from: %s' ), $sitename );
			$message  = esc_html( 'The following is a simple test email to verify that emails are correctly being delivered from your website.' );

			$emails->send( $email, $subject, $message );

		} else {
			wp_die( -1, 403 );
		}

		wp_send_json_success();

	}

	/**
	 * Enable email
	 */
	public function wpum_enabled_email() {

		check_ajax_referer( 'wpum_test_email', 'nonce' );

		$enabled = filter_input( INPUT_POST, 'enabled', FILTER_SANITIZE_STRING );
		$key     = filter_input( INPUT_POST, 'key', FILTER_SANITIZE_STRING );

		if ( ! empty( $key ) && current_user_can( apply_filters( 'wpum_admin_pages_capability', 'manage_options' ) ) && is_admin() ) {
			$emails                    = wpum_get_emails();
			$emails[ $key ]['enabled'] = $enabled;

			update_option( 'wpum_email', $emails );

		} else {
			wp_die( -1, 403 );
		}

		wp_send_json_success();

	}
}

new WPUM_Emails_List();
