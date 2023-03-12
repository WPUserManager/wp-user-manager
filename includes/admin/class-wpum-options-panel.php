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
	public function init() {
		// Setup labels for the options panel.
		add_filter( 'wpum_labels', array( $this, 'register_labels' ) );

		$this->panel = new \WPUM\TDP\OptionsKit( 'wpum' );
		$this->panel->set_page_title( 'WP User Manager Settings' );

		// Add a logo to the options panel.
		$this->panel->add_image( WPUM_PLUGIN_URL . 'assets/images/logo.svg' );

		// Register action buttons for the header.
		$this->register_action_buttons();

		// Setup the options panel menu.
		add_filter( 'wpum_menu', array( $this, 'setup_menu' ) );

		// Register settings tabs.
		add_filter( 'wpum_settings_tabs', array( $this, 'register_settings_tabs' ) );
		add_filter( 'wpum_registered_settings_sections', array( $this, 'register_settings_subsections' ) );

		// Register settings fields for the options panel.
		add_filter( 'wpum_registered_settings', array( $this, 'register_settings' ) );
	}

	/**
	 * Register action buttons for the options panel.
	 */
	private function register_action_buttons() {
		$this->panel->add_action_button(
			array(
				'title' => __( 'View Addons', 'wp-user-manager' ),
				'url'   => 'https://wpusermanager.com/addons/?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=settings-header',
			)
		);

		$this->panel->add_action_button(
			array(
				'title' => __( 'Read documentation', 'wp-user-manager' ),
				'url'   => 'https://wpusermanager.com/docs/?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=settings-header',
			)
		);
	}

	/**
	 * Setup the menu for the options panel.
	 *
	 * @param array $menu original settings of the menu.
	 *
	 * @return array
	 */
	public function setup_menu( $menu ) {
		return array(
			'parent'     => 'users.php',
			'page_title' => __( 'WP User Manager Settings', 'wp-user-manager' ),
			'menu_title' => __( 'Settings', 'wp-user-manager' ),
			'capability' => apply_filters( 'wpum_admin_pages_capability', 'manage_options' ),
		);
	}

	/**
	 * Register settings tabs for the options panel.
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function register_settings_tabs( $tabs ) {
		$tabs = array(
			'general'   => __( 'General', 'wp-user-manager' ),
			'emails'    => __( 'Emails', 'wp-user-manager' ),
			'profiles'  => __( 'Profiles', 'wp-user-manager' ),
			'redirects' => __( 'Redirects', 'wp-user-manager' ),
		);

		return $tabs;
	}

	/**
	 * Register subsections for the option tabs.
	 *
	 * @param array $sections
	 *
	 * @return array
	 */
	public function register_settings_subsections( $sections ) {
		return array(
			'general'      => array(
				'login' => __( 'Login Settings', 'wp-user-manager' ),
				'misc'  => __( 'Misc Settings', 'wp-user-manager' ),
			),
			'registration' => array(),
			'emails'       => array(
				'admin_notifications' => __( 'Administration Notifications', 'wp-user-manager' ),
			),
			'profiles'     => array(
				'profiles_content'     => __( 'Profiles Content', 'wp-user-manager' ),
				'profiles_permissions' => __( 'Permissions', 'wp-user-manager' ),
				'account'              => __( 'Account', 'wp-user-manager' ),
			),
			'redirects'    => array(
				'backend_redirects' => __( 'Backend Redirects', 'wp-user-manager' ),
			),
		);
	}

	/**
	 * Register labels for the options panel.
	 *
	 * @param array $labels
	 *
	 * @return array
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
	 *
	 * @return array
	 */
	public function register_settings( $settings ) {
		$plugin_settings = array(
			// General tab settings.
			'general'              => array(
				array(
					'id'      => 'login_page',
					'name'    => __( 'Login Page', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the login shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'password_recovery_page',
					'name'    => __( 'Password Recovery Page', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the password recovery shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'registration_page',
					'name'    => __( 'Registration Page', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the registration shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'account_page',
					'name'    => __( 'Account Page', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the account shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
				array(
					'id'      => 'profile_page',
					'name'    => __( 'Profile Page', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you have added the profile shortcode.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
			),
			'login'                => array(
				array(
					'id'   => 'lock_wplogin',
					'name' => __( 'Lock Access to wp-login.php', 'wp-user-manager' ),
					'desc' => __( 'Enable to lock access to wp-login.php. Users will be redirected to the WPUM login page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'      => 'login_method',
					'name'    => __( 'Allow Users to Login With', 'wp-user-manager' ),
					'type'    => 'select',
					'std'     => 'email',
					'options' => wpum_get_login_methods(),
				),
				array(
					'id'   => 'lock_complete_site',
					'name' => __( 'Prevent site access to visitors', 'wp-user-manager' ),
					'desc' => __( 'Prevent access to the site for visitors who are not logged in. Users will be redirected to the login page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'     => 'lock_complete_site_allow_register',
					'name'   => __( 'Allow site registration', 'wp-user-manager' ),
					'desc'   => __( 'Allow access to the \'Register page\' for site registration despite the site being locked.', 'wp-user-manager' ),
					'type'   => 'checkbox',
					'toggle' => array(
						'key'   => 'lock_complete_site',
						'value' => true,
					),
				),
			),
			'misc'                 => array(
				array(
					'id'       => 'adminbar_roles',
					'name'     => __( 'Admin Bar', 'wp-user-manager' ),
					'desc'     => __( 'Hide WordPress admin bar for specific user roles.', 'wp-user-manager' ),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles', 'wp-user-manager' ) ),
					'options'  => wpum_get_roles(),
				),
				array(
					'id'       => 'wp_admin_roles',
					'name'     => __( 'Restrict Dashboard Access', 'wp-user-manager' ),
					'desc'     => __( 'Restrict access to the WordPress dashboard for specific user roles. Administrators will always have access.', 'wp-user-manager' ),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles', 'wp-user-manager' ) ),
					'options'  => wpum_get_roles(),
				),
				array(
					'id'   => 'exclude_usernames',
					'name' => __( 'Excluded Usernames', 'wp-user-manager' ),
					'desc' => __( 'Enter the usernames that you wish to disable. Separate each username on a new line.', 'wp-user-manager' ),
					'type' => 'textarea',
				),
				array(
					'id'   => 'roles_editor',
					'name' => __( 'Roles Editor', 'wp-user-manager' ),
					'desc' => __( 'Enable the roles editor in the Users menu.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'allow_multiple_user_roles',
					'name' => __( 'Allow Multiple Roles', 'wp-user-manager' ),
					'desc' => __( 'Users can be assigned multiple roles.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			),
			'registration'         => array(
				array(
					'id'   => 'allow_role_select',
					'name' => __( 'Allow Role Section', 'wp-user-manager' ),
					'desc' => __( 'Enable to allow users to select a user role on registration.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'       => 'register_roles',
					'name'     => __( 'Allowed Roles', 'wp-user-manager' ),
					'desc'     => __( 'Select which roles can be selected upon registration.', 'wp-user-manager' ),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles', 'wp-user-manager' ) ),
					'options'  => wpum_get_roles(),
					'toggle'   => array(
						'key'   => 'allow_role_select',
						'value' => true,
					),
				),
				array(
					'id'   => 'login_after_registration',
					'name' => __( 'Login After Registration', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to automatically authenticate users after registration.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'enable_terms',
					'name' => __( 'Enable Terms & Conditions', 'wp-user-manager' ),
					'desc' => __( 'Enable to force users to agree to your terms before registering an account.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'      => 'terms_page',
					'name'    => __( 'Terms Page', 'wp-user-manager' ),
					'desc'    => __( 'Select the page that contains your terms.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
					'toggle'  => array(
						'key'   => 'enable_terms',
						'value' => true,
					),
				),
			),
			'emails'               => array(
				array(
					'id'   => 'from_name',
					'name' => __( 'From Name', 'wp-user-manager' ),
					'desc' => __( 'The name emails are said to come from. This should probably be your site name.', 'wp-user-manager' ),
					'type' => 'text',
					'std'  => get_option( 'blogname' ),
				),
				array(
					'id'   => 'from_email',
					'name' => __( 'From Email', 'wp-user-manager' ),
					'desc' => __( 'This will act as the "from" and "reply-to" address.', 'wp-user-manager' ),
					'type' => 'text',
					'std'  => get_option( 'admin_email' ),
				),
				array(
					'id'      => 'email_template',
					'name'    => __( 'Email Template', 'wp-user-manager' ),
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
			),
			'admin_notifications'  => array(
				array(
					'id'   => 'disable_admin_register_email',
					'name' => __( 'Disable Admin Registration Email', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to stop receiving notifications when a new user registers.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'disable_admin_password_recovery_email',
					'name' => __( 'Disable Admin Password Recovery Email', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to stop receiving notifications when user resets their password.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			),
			'profiles'             => array(
				array(
					'id'   => 'disable_profiles',
					'name' => __( 'Disable User Profiles', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to disable frontend user profiles.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'custom_avatars',
					'name' => __( 'Custom Avatars', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to allow users to upload custom avatars for their profiles.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'default_avatar',
					'name' => __( 'Default Avatar', 'wp-user-manager' ),
					'desc' => __( 'Select a default image to be used for users who don\'t have a custom avatar or Gravatar registered with their email address.', 'wp-user-manager' ),
					'type' => 'file',
				),
				array(
					'id'   => 'disable_profile_cover',
					'name' => __( 'Disable Profile Cover Image', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to prevent users from uploading a custom profile cover image.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'disable_strong_passwords',
					'name' => __( 'Disable Strong Passwords', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to disable the built-in strong passwords validation system of WP User Manager.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'      => 'default_display_name',
					'name'    => __( 'Default Display Name', 'wp-user-manager' ),
					'desc'    => __( 'Select the default format for the user display name at registration. The user can change their display name later in their account.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_display_name_options(),
					'std'     => 'display_username',
				),
				array(
					'id'   => 'obfuscate_display_name_emails',
					'name' => __( 'Obfuscate Display Name Emails', 'wp-user-manager' ),
					'desc' => __( 'When usernames are email addresses, and username is the used as the display name, partially obfuscate email addresses for privacy.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			),
			'profiles_content'     => array(
				array(
					'id'   => 'profile_posts',
					'name' => __( 'Display Posts', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to display users submitted post on their profile page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'profile_comments',
					'name' => __( 'Display Comments', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to display users submitted comments on their profile page.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'     => 'number_of_comments',
					'name'   => __( 'Number of Comments', 'wp-user-manager' ),
					'desc'   => __( 'The default number of comments displayed in profile page.', 'wp-user-manager' ),
					'type'   => 'text',
					'std'    => 10,
					'toggle' => array(
						'key'   => 'profile_comments',
						'value' => true,
					),
				),
			),
			'account'              => array(
				array(
					'id'   => 'current_password',
					'name' => __( 'Require Current Password', 'wp-user-manager' ),
					'desc' => __( 'Ask user for current password when resetting password on the account page', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			),
			'profiles_permissions' => array(
				array(
					'id'   => 'guests_can_view_profiles',
					'name' => __( 'Allow Guests to View Profiles', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to allow guests to view users profiles.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'members_can_view_profiles',
					'name' => __( 'Allow Members to View Profiles', 'wp-user-manager' ),
					'desc' => __( 'Enable this option to allow members to view users profiles. If disabled, users can only see their own profile.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
				array(
					'id'   => 'members_can_set_privacy',
					'name' => __( 'Allow Members to set their profile privacy', 'wp-user-manager' ),
					'desc' => __( 'Adds a Privacy account tab where members can override the global settings above.', 'wp-user-manager' ),
					'type' => 'checkbox',
				),
			),
			'redirects'            => array(
				array(
					'id'      => 'login_redirect',
					'name'    => __( 'After Login', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users after they login. If empty it will reload the login page.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_redirect_pages(),
				),
				array(
					'id'      => 'logout_redirect',
					'name'    => __( 'After Logout', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users after they logout. If empty will return to wp-login.php', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_redirect_pages(),
				),
				array(
					'id'      => 'registration_redirect',
					'name'    => __( 'After Registration', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users after they successfully register. If empty a message will be displayed instead.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_redirect_pages(),
				),
			),
			'backend_redirects'    => array(
				array(
					'id'      => 'wp_login_signup_redirect',
					'name'    => __( 'Backend Register', 'wp-user-manager' ),
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
					'name'    => __( 'Backend Profile', 'wp-user-manager' ),
					'desc'    => __( 'Select the page where you want to redirect users who try to access their profile on the backend.', 'wp-user-manager' ),
					'type'    => 'multiselect',
					'options' => wpum_get_pages(),
				),
			),
		);

		return array_merge( $settings, $plugin_settings );
	}

}
