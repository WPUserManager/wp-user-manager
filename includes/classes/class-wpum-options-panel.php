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
			'login'        => __( 'Login' ),
			'registration' => __( 'Registration' ),
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
			'general' => array(
				'emails' => __( 'Emails' ),
				'extra'  => __( 'Extra' )
			)
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
			]
		];

		return array_merge( $settings, $plugin_settings );

	}

}

new WPUM_Options_Panel;
