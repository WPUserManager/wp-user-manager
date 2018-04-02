<?php
/**
 * Handles the email templates list in the backend.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_ajax_wpum_send_test_email', array( $this, 'send_test_email' ) );
	}

	/**
	 * Add new menu page to the "Users" menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Emails' ),
			esc_html__( 'Emails' ),
			'manage_options',
			'wpum-emails',
			[ $this, 'display_emails_list' ]
		);
	}

	/**
	 * Add the required scripts to the emails editor page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		if( isset( $_GET['page'] ) && $_GET['page'] == 'wpum-emails' ) {
			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-emails-editor', 'http://localhost:8080/emails.js', array(), WPUM_VERSION, true );
				wp_enqueue_script( 'wpum-emails-editor' );
			} else {
				wp_die( 'Vue build missing' );
			}

			$js_variables = [
				'ajax'          => admin_url( 'admin-ajax.php' ),
				'customizeurl'  => admin_url( 'customize.php' ),
				'url'           => site_url( '/' ),
				'pluginURL'     => WPUM_PLUGIN_URL,
				'nonce'         => wp_create_nonce( 'wpum_test_email' ),
				'default_email' => get_option( 'admin_email' ),
				'emails'        => wpum_get_registered_emails(),
				'labels'        => [
					'title'             => esc_html__( 'WP User Manager Emails Customization' ),
					'email'             => esc_html__( 'Email' ),
					'description'       => esc_html__( 'Description' ),
					'recipients'        => esc_html__( 'Recipient(s)' ),
					'tooltip_automatic' => esc_html__( 'Sent automatically' ),
					'tooltip_manual'    => esc_html__( 'Manually triggered' ),
					'placeholder'       => esc_html__( 'Enter email address...' ),
					'customize'         => esc_html__( 'Customize' ),
					'send'              => esc_html__( 'Send test email' ),
					'success'           => esc_html__( 'Test email successfully sent.' ),
					'error'             => esc_html__( 'Something went wrong while sending the test email. Please verify the email address you typed is correct or check your server logs.' )
				]
			];

			wp_localize_script( 'wpum-emails-editor', 'wpumEmailsEditor', $js_variables );

		}

	}

	/**
	 * Display the list of available emails.
	 *
	 * @return string
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

		$email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : false;

		if( $email && is_email( $email ) && current_user_can( 'manage_options' ) && is_admin() ) {

			$emails       = new WPUM_Emails;
			$emails->__set( 'heading', esc_html__( 'Test email' ) );

			$sitename = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$subject  = sprintf( esc_html( 'Test email from: %s' ), $sitename );
			$message  = esc_html( 'The following is a simple test email to verify that emails are correctly being delivered from your website.' );

			$emails->send( $email, $subject, $message );

		} else {
			wp_die( -1, 403 );
		}

		wp_send_json_success();

	}

}

new WPUM_Emails_List;
