<?php
/**
 * Register an options panel.
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
 * Handles the display of the options panel.
 */
class WPUM_Options_Panel {

	/**
	 * Holds the options panel controller.
	 *
	 * @var object
	 */
	protected $panel;

	/**
	 * Get things started.
	 */
	public function __construct() {

		// Setup labels for the options panel.
		add_filter( 'wpum_labels', [ $this, 'register_labels' ] );

		$this->panel = new TDP\OptionsKit( 'wpum' );
		$this->panel->set_page_title( 'WP User Manager Settings' );

		// Add a logo to the options panel.
		$this->panel->add_image( WPUM_PLUGIN_URL . 'assets/images/logo.svg' );

		// Register action buttons for the header.
		$this->register_action_buttons();

		// Setup the options panel menu.
		add_filter( 'wpum_menu', [ $this, 'setup_menu' ] );

		// Register settings tabs.
		add_filter( 'wpum_settings_tabs', [ $this, 'register_settings_tabs' ] );
		add_filter( 'wpum_registered_settings_sections', [ $this, 'register_settings_subsections' ] );

		// Register settings fields for the options panel.
		add_filter( 'wpum_registered_settings', [ $this, 'register_settings' ] );

	}

	/**
	 * Register action buttons for the options panel.
	 *
	 * @return void
	 */
	private function register_action_buttons() {

		$this->panel->add_action_button(
			array(
				'title' => __( 'View Addons', 'wp-user-manager' ),
				'url'   => 'http://wpusermanager.com/addons/',
			)
		);

		$this->panel->add_action_button(
			array(
				'title' => __( 'Read documentation', 'wp-user-manager' ),
				'url'   => 'https://docs.wpusermanager.com/',
			)
		);

	}

	/**
	 * Setup the menu for the options panel.
	 *
	 * @param array $menu original settings of the menu.
	 * @return void
	 */
	public function setup_menu( $menu ) {

		return array(
			'parent'     => 'users.php',
			'page_title' => __( 'WP User Manager Settings', 'wp-user-manager' ),
			'menu_title' => __( 'Settings', 'wp-user-manager' ),
			'capability' => 'manage_options',
		);

	}

	/**
	 * Register settings tabs for the options panel.
	 *
	 * @param array $tabs
	 * @return void
	 */
	public function register_settings_tabs( $tabs ) {

		$tabs = array(
			'general'      => __( 'General', 'wp-user-manager' ),
			'registration' => __( 'Registration', 'wp-user-manager' ),
			'emails'       => __( 'Emails', 'wp-user-manager' ),
			'profiles'     => __( 'Profiles', 'wp-user-manager' ),
			'redirects'    => __( 'Redirects', 'wp-user-manager' ),
		);

		return $tabs;

	}

	/**
	 * Register subsections for the option tabs.
	 *
	 * @param array $sections
	 * @return void
	 */
	public function register_settings_subsections( $sections ) {

		$sections = array(
			'general'      => [
				'login' => __( 'Login settings', 'wp-user-manager' ),
				'misc'  => __( 'Misc settings', 'wp-user-manager' ),
			],
			'registration' => [
				'terms' => __( 'Terms & Conditions', 'wp-user-manager' ),
			],
			'emails'       => [
				'admin_notifications' => __( 'Administration notifications', 'wp-user-manager' ),
			],
			'profiles'     => [
				'profiles_content' => __( 'Profiles content', 'wp-user-manager' ),
			],
			'redirects'    => [
				'backend_redirects' => __( 'Backend redirects', 'wp-user-manager' ),
			],
		);

		return $sections;

	}

	/**
	 * Register labels for the options panel.
	 *
	 * @param array $labels
	 * @return void
	 */
	public function register_labels( $labels ) {

		$labels = array(
			'save'         => __( 'Save Changes', 'wp-user-manager' ),
			'success'      => __( 'Settings successfully saved.', 'wp-user-manager' ),
			'upload'       => __( 'Select file', 'wp-user-manager' ),
			'upload-title' => __( 'Insert file', 'wp-user-manager' ),
			'multiselect'  => array(
				'selectLabel'   => __( 'Press enter to select', 'wp-user-manager' ),
				'SelectedLabel' => __( 'Selected', 'wp-user-manager' ),
				'deselectLabel' => __( 'Press enter to remove', 'wp-user-manager' ),
				'placeholder'   => __( 'Select option (type to search)', 'wp-user-manager' ),
			),
			'error'        => __( 'Whoops! Something went wrong. Please check the following fields for more info:', 'wp-user-manager' ),
		);

		return $labels;

	}

	/**
	 * Register settings for the general tab.
	 *
	 * @param array $settings
	 * @return void
	 */
	public function register_settings( $settings ) {

		$plugin_settings = [
			// General tab settings.
			'general'             => [
				array(
					'id'      => 'login_page',
					'name'    => __( 'Login page:', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the login shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'password_recovery_page',
					'name'    => __( 'Password recovery page:', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the password recovery shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'registration_page',
					'name'    => __( 'Registration page:', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the registration shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'account_page',
					'name'    => __( 'Account page:', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the account shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'profile_page',
					'name'    => __( 'Profile page:', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the profile shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
			],
			'login'               => [
				array(
					'id'   => 'lock_wplogin',
					'name' => __( 'Lock access to wp-login.php:', 'wp-user-manager' ),
					'desc' => __( 'Enable to lock access to wp-login.php. Users will be redirected to the WPUM login page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'      => 'login_method',
					'name'    => __( 'Allow users to login with:', 'wp-user-manager' ),
					'type'    => 'select',
					'std'     => 'email',
					'options' => wpum_get_login_methods(),
				),
			],
			'misc'                => [
				array(
					'id'       => 'adminbar_roles',
					'name'     => __( 'Admin Bar:', 'wp-user-manager' ),
					'desc'     => __( 'Hide WordPress admin bar for specific user roles.', 'wp-user-manager' ),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles from the list.', 'wp-user-manager' ) ),
					'options'  => wpum_get_roles(),
				),
				array(
					'id'   => 'exclude_usernames',
					'name' => __( 'Excluded usernames:', 'wp-user-manager' ),
					'desc' => __( 'Enter the usernames that you wish to disable. Separate each username on a new line.', 'wp-user-manager' ),
					'type' => 'textarea',
				),
			],
			'registration'        => [
				array(
					'id'   => 'login_after_registration',
					'name' => __( 'Login after registration:', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to automatically authenticate users after registration.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'allow_role_select',
					'name' => __( 'Allow role section:', 'wp-user-manager' ),
					'desc' => __( 'Enable to allow users to select a user role on registration.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'       => 'register_roles',
					'name'     => __( 'Allowed Roles:', 'wp-user-manager' ),
					'desc'     => __( 'Select which roles can be selected upon registration.', 'wp-user-manager' ),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles from the list.', 'wp-user-manager' ) ),
					'options'  => wpum_get_roles(),
				),
			],
			'terms'               => [
				array(
					'id'   => 'enable_terms',
					'name' => __( 'Enable terms & conditions:', 'wp-user-manager' ),
					'desc' => __( 'Enable to force users to agree to your terms before registering an account.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'      => 'terms_page',
					'name'    => __( 'Terms Page:', 'wp-user-manager' ),
					'desc'    => __( 'Select the page that contains your terms.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
			],
			'emails'              => [
				array(
					'id'   => 'from_name',
					'name' => __( 'From Name:', 'wp-user-manager' ),
					'desc' => __( 'The name emails are said to come from. This should probably be your site name.', 'wp-user-manager' ),
					'type' => 'text',
					'std'  => get_option( 'blogname' ),
				),
				array(
					'id'   => 'from_email',
					'name' => __( 'From Email:', 'wp-user-manager' ),
					'desc' => __( 'This will act as the "from" and "reply-to" address.', 'wp-user-manager' ),
					'type' => 'text',
					'std'  => get_option( 'admin_email' ),
				),
				array(
					'id'      => 'email_template',
					'name'    => __( 'Email template:', 'wp-user-manager' ),
					'desc'    => __( 'Select the email template you wish to use for all emails sent by WPUM.', 'wp-user-manager' ),
					'type'    => 'select',
					'std'     => 'default',
					'options' => wpum_get_email_templates(),
				),
				array(
					'id'   => 'email_logo',
					'name' => __( 'Logo', 'wp-user-manager' ),
					'desc' => __( 'Upload or choose a logo to be displayed at the top of emails. Displayed on HTML emails only.', 'wp-user-manager' ),
					'type' => 'file',
				),
			],
			'admin_notifications' => [
				array(
					'id'   => 'disable_admin_register_email',
					'name' => __( 'Disable admin registration email:', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to stop receiving notifications when a new user registers.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'disable_admin_password_recovery_email',
					'name' => __( 'Disable admin password recovery email:', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to stop receiving notifications when a new user resets his password.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			],
			'profiles'            => [
				array(
					'id'   => 'guests_can_view_profiles',
					'name' => __( 'Allow guests to view profiles', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to allow guests to view users profiles.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'members_can_view_profiles',
					'name' => __( 'Allow members to view profiles', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to allow members to view users profiles. If disabled, users can only see their own profile.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'custom_avatars',
					'name' => __( 'Custom Avatars', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to allow users to upload custom avatars for their profiles.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'disable_strong_passwords',
					'name' => __( 'Disable strong passwords', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to disable the built-in strong passwords validation system of WP User Manager.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			],
			'profiles_content'    => [
				array(
					'id'   => 'profile_posts',
					'name' => __( 'Display posts', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to display users submitted post on their profile page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'profile_comments',
					'name' => __( 'Display comments', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to display users submitted comments on their profile page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			],
			'redirects'           => [
				array(
					'id'      => 'login_redirect',
					'name'    => __( 'After login', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users after they login. Leave blank to redirect users to the previously visited page.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'logout_redirect',
					'name'    => __( 'After logout', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users after they logout. If empty will return to wp-login.php', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'registration_redirect',
					'name'    => __( 'After registration', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users after they successfully register. If empty a message will be displayed instead.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
			],
			'backend_redirects'   => [
				array(
					'id'      => 'wp_login_signup_redirect',
					'name'    => __( 'Backend register', 'wp-user-manager' ),
					'desc'    => __( 'Select a page if you wish to redirect users who try to signup through wp-login.php', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'wp_login_password_redirect',
					'name'    => __( 'Backend lost password', 'wp-user-manager' ),
					'desc'    => __( 'Select a page if you wish to redirect users who try to recover a lost password through wp-login.php', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'backend_profile_redirect',
					'name'    => __( 'Backend profile', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users who try to access their profile on the backend.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
			],
		];

		return array_merge( $settings, $plugin_settings );

	}

}

new WPUM_Options_Panel;
