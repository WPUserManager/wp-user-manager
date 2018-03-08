<?php
/**
 * Register an options panel.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

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

		$this->panel->add_action_button( array(
    		'title' => __( 'View Addons' ),
    		'url'   => 'http://wpusermanager.com/addons/'
		) );

		$this->panel->add_action_button( array(
    		'title' => __( 'Read documentation' ),
    		'url'   => 'https://docs.wpusermanager.com/'
		) );

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
			'page_title' => __( 'WP User Manager Settings' ),
			'menu_title' => __( 'Settings' ),
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
			'general'      => __( 'General' ),
			'registration' => __( 'Registration' ),
			'emails'       => __( 'Emails' ),
			'profiles'     => __( 'Profiles' ),
			'redirects'    => __( 'Redirects' ),
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
			'general' => [
				'login' => __( 'Login settings' ),
				'misc'  => __( 'Misc settings' )
			],
			'registration' => [
				'terms' => __( 'Terms & Conditions' )
			],
			'emails' => [
				'admin_notifications' => __( 'Administration notifications' )
			],
			'profiles' => [
				'profiles_content' => __( 'Profiles content' )
			],
			'redirects' => [
				'backend_redirects' => __( 'Backend redirects' )
			]
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
			'save'         => __( 'Save Changes' ),
			'success'      => __( 'Settings successfully saved.' ),
			'upload'       => __( 'Select file' ),
			'upload-title' => __( 'Insert file' ),
			'multiselect'  => array(
				'selectLabel'   => __( 'Press enter to select' ),
				'SelectedLabel' => __( 'Selected' ),
				'deselectLabel' => __( 'Press enter to remove' ),
				'placeholder'   => __( 'Select option (type to search)' ),
			),
			'error'        => __( 'Whoops! Something went wrong. Please check the following fields for more info:' ),
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
			'general' => [
				array(
					'id'       => 'login_page',
					'name'     => __( 'Login page:' ),
					'desc'     => __( 'Select the page where you have added the login shortcode.' ),
					'type'     => 'multiselect',
					'options'  => wpum_get_pages()
				),
				array(
					'id'       => 'password_recovery_page',
					'name'     => __( 'Password recovery page:' ),
					'desc'     => __( 'Select the page where you have added the password recovery shortcode.' ),
					'type'     => 'multiselect',
					'options'  => wpum_get_pages()
				),
				array(
					'id'       => 'registration_page',
					'name'     => __( 'Registration page:' ),
					'desc'     => __( 'Select the page where you have added the registration shortcode.' ),
					'type'     => 'multiselect',
					'options'  => wpum_get_pages()
				),
				array(
					'id'       => 'account_page',
					'name'     => __( 'Account page:' ),
					'desc'     => __( 'Select the page where you have added the account shortcode.' ),
					'type'     => 'multiselect',
					'options'  => wpum_get_pages()
				),
				array(
					'id'       => 'profile_page',
					'name'     => __( 'Profile page:' ),
					'desc'     => __( 'Select the page where you have added the profile shortcode.' ),
					'type'     => 'multiselect',
					'options'  => wpum_get_pages()
				),
			],
			'login' => [
				array(
					'id'   => 'lock_wplogin',
					'name' => __( 'Lock access to wp-login.php:', 'wpum' ),
					'desc' => __('Enable to lock access to wp-login.php. Users will be redirected to the WPUM login page.', 'wpum'),
					'type' => 'checkbox'
				),
				array(
					'id'      => 'login_method',
					'name'    => __( 'Allow users to login with:', 'wpum' ),
					'type'    => 'select',
					'std'     => 'email',
					'options' => wpum_get_login_methods()
				),
			],
			'misc' => [
				array(
					'id'       => 'adminbar_roles',
					'name'     => __( 'Admin Bar:', 'wpum' ),
					'desc'     => __('Hide WordPress admin bar for specific user roles.', 'wpum'),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles from the list.' ) ),
					'options'  => wpum_get_roles()
				),
				array(
					'id'   => 'exclude_usernames',
					'name' => __( 'Excluded usernames:', 'wpum' ),
					'desc' => __('Enter the usernames that you wish to disable. Separate each username on a new line.', 'wpum'),
					'type' => 'textarea'
				),
			],
			'registration' => [
				array(
					'id'   => 'custom_passwords',
					'name' => __( 'Users custom passwords:', 'wpum' ),
					'desc' => __('Enable to allow users to set custom passwords on the registration page.', 'wpum'),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'login_after_registration',
					'name' => __( 'Login after registration:', 'wpum' ),
					'desc' => __('Enable this option to authenticate users after registration.', 'wpum'),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'allow_role_select',
					'name' => __( 'Allow role section:', 'wpum' ),
					'desc' => __('Enable to allow users to select a user role on registration.', 'wpum'),
					'type' => 'checkbox'
				),
				array(
					'id'       => 'register_roles',
					'name'     => __( 'Allowed Roles:', 'wpum' ),
					'desc'     => __('Select which roles can be selected upon registration.', 'wpum'),
					'type'     => 'multiselect',
					'multiple' => true,
					'labels'   => array( 'placeholder' => __( 'Select one or more user roles from the list.' ) ),
					'options'  => wpum_get_roles()
				),
			],
			'terms' => [
				array(
					'id'   => 'enable_terms',
					'name' => __( 'Enable terms & conditions:', 'wpum' ),
					'desc' => __('Enable to force users to agree to your terms before registering an account.', 'wpum'),
					'type' => 'checkbox'
				),
				array(
					'id'      => 'terms_page',
					'name'    => __( 'Terms Page:', 'wpum' ),
					'desc'    => __('Select the page that contains your terms.', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
			],
			'emails' => [
				array(
					'id'   => 'from_name',
					'name' => __( 'From Name:', 'wpum' ),
					'desc' => __( 'The name emails are said to come from. This should probably be your site name.', 'wpum' ),
					'type' => 'text',
					'std'  => get_option( 'blogname' )
				),
				array(
					'id'   => 'from_email',
					'name' => __( 'From Email:', 'wpum' ),
					'desc' => __( 'This will act as the "from" and "reply-to" address.', 'wpum' ),
					'type' => 'text',
					'std'  => get_option( 'admin_email' )
				),
				array(
					'id'   => 'email_template',
					'name' => __( 'Email template:', 'wpum' ),
					'desc' => __( 'Select the email template you wish to use for all emails sent by WPUM.', 'wpum' ),
					'type' => 'select',
					'std'  => 'default',
					'options' => wpum_get_email_templates()
				),
				array(
					'id'   => 'email_logo',
					'name' => __( 'Logo', 'wpum' ),
					'desc' => __( 'Upload or choose a logo to be displayed at the top of emails. Displayed on HTML emails only.', 'wpum' ),
					'type' => 'file'
				),
			],
			'admin_notifications' => [
				array(
					'id'   => 'disable_admin_register_email',
					'name' => __( 'Disable admin registration email:', 'wpum' ),
					'desc' => __( 'Enable this option to stop receiving notifications when a new user registers.', 'wpum' ),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'disable_admin_password_recovery_email',
					'name' => __( 'Disable admin password recovery email:', 'wpum' ),
					'desc' => __( 'Enable this option to stop receiving notifications when a new user resets his password.', 'wpum' ),
					'type' => 'checkbox'
				),
			],
			'profiles' => [
				array(
					'id'   => 'guests_can_view_profiles',
					'name' => __( 'Allow guests to view profiles', 'wpum' ),
					'desc' => __( 'Enable this option to allow guests to view users profiles.', 'wpum' ),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'members_can_view_profiles',
					'name' => __( 'Allow members to view profiles', 'wpum' ),
					'desc' => __( 'Enable this option to allow members to view users profiles. If disabled, users can only see their own profile.', 'wpum' ),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'custom_avatars',
					'name' => __( 'Custom Avatars', 'wpum' ),
					'desc' => __( 'Enable this option to allow users to upload custom avatars for their profiles.', 'wpum' ),
					'type' => 'checkbox'
				),
			],
			'profiles_content' => [
				array(
					'id'   => 'profile_posts',
					'name' => __( 'Display posts', 'wpum' ),
					'desc' => __( 'Enable this option to display users submitted post on their profile page.', 'wpum' ),
					'type' => 'checkbox'
				),
				array(
					'id'   => 'profile_comments',
					'name' => __( 'Display comments', 'wpum' ),
					'desc' => __( 'Enable this option to display users submitted comments on their profile page.', 'wpum' ),
					'type' => 'checkbox'
				),
			],
			'redirects' => [
				array(
					'id'      => 'login_redirect',
					'name'    => __( 'After login', 'wpum' ),
					'desc'    => __('Select the page where you want to redirect users after they login.', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
				array(
					'id'      => 'logout_redirect',
					'name'    => __( 'After logout', 'wpum' ),
					'desc'    => __( 'Select the page where you want to redirect users after they logout. If empty will return to wp-login.php', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
				array(
					'id'      => 'registration_redirect',
					'name'    => __( 'After registration', 'wpum' ),
					'desc'    => __( 'Select the page where you want to redirect users after they successfully register. If empty a message will be displayed instead.', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
			],
			'backend_redirects' => [
				array(
					'id'      => 'wp_login_signup_redirect',
					'name'    => __( 'Backend register', 'wpum' ),
					'desc'    => __( 'Select a page if you wish to redirect users who try to signup through wp-login.php', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
				array(
					'id'      => 'wp_login_password_redirect',
					'name'    => __( 'Backend lost password', 'wpum' ),
					'desc'    => __( 'Select a page if you wish to redirect users who try to recover a lost password through wp-login.php', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
				array(
					'id'      => 'backend_profile_redirect',
					'name'    => __( 'Backend profile', 'wpum' ),
					'desc'    => __( 'Select the page where you want to redirect users who try to access their profile on the backend.', 'wpum'),
					'type'    => 'multiselect',
					'options' => wpum_get_pages()
				),
			]
		];

		return array_merge( $settings, $plugin_settings );

	}

}

new WPUM_Options_Panel;
