<?php
/*
Plugin Name: WP User Manager
Plugin URI:  https://wpusermanager.com
Description: Beautifully simple user profile directories with frontend login, registration and account customization. WP User Manager is the best solution to manage your community and your users for WordPress.
Version:     2.3.7
Author:      WP User Manager
Author URI:  https://wpusermanager.com
License:     GPLv3+
Text Domain: wp-user-manager
Domain Path: /languages
*/

/**
 * WP User Manager.
 *
 * Copyright (c) 2020 WP User Manager
 *
 * WP User Manager. is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * WP User Manager. is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * @author     WP User Manager
 * @version    2.3.6
 * @copyright  (c) 2020 WP User Manager
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 * @package    wp-user-manager
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_User_Manager' ) ) :

	/**
	 * Main WP_User_Manager class.
	 */
	final class WP_User_Manager {

		/**
		 * @var string
		 */
		protected $version = '2.3.7';

		/**
		 * WPUM Instance.
		 *
		 * @var WP_User_Manager the WPUM Instance
		 */
		protected static $_instance;

		/**
		 * Holds the admin notice creation helper class.
		 *
		 * @var object
		 */
		public $notices;

		/**
		 * Store the template loader object.
		 *
		 * @var object
		 */
		public $templates;

		/**
		 * Store all forms.
		 *
		 * @var object
		 */
		public $forms;

		/**
		 * The email templates handler.
		 *
		 * @var object
		 */
		public $emails;

		/**
		 * The fields groups handler.
		 *
		 * @var WPUM_DB_Fields_Groups
		 */
		public $fields_groups;

		/**
		 * The fields handler.
		 *
		 * @var WPUM_DB_Fields
		 */
		public $fields;

		/**
		 * Store update and delete fields metas.
		 *
		 * @var WPUM_DB_Field_Meta
		 */
		public $field_meta;

		/**
		 * Registration forms db handler.
		 *
		 * @var WPUM_DB_Registration_Forms
		 */
		public $registration_forms;

		/**
		 * Registration form meta handler.
		 *
		 * @var WPUM_DB_Registration_Form_Meta
		 */
		public $registration_form_meta;

		/**
		 * Holds the handler for the fields search meta keys.
		 *
		 * @var object
		 */
		public $search_meta;

		/**
		 * Holds the html input fields generator.
		 *
		 * @var object
		 */
		public $elements;

		/**
		 * Async process object holder.
		 *
		 * @var object
		 */
		public $async_process;

		/**
		 * Main WPUM Instance.
		 *
		 * Ensures that only one instance of WPUM exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @return WP_User_Manager
		 */
		public static function instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Get things up and running.
		 */
		public function __construct() {

			// Verify the plugin can run first. If not, disable the plugin automagically.
			$this->plugin_can_run();

			$this->setup_constants();
			$this->autoload();
			$this->autoload_options();
			$this->includes();

			$this->init_hooks();

		}

		/**
		 * Autoload composer and other required classes.
		 *
		 * @return void
		 */
		private function autoload() {
			require __DIR__ . '/vendor/autoload.php';
		}

		/**
		 * Load WPUM options.
		 *
		 * @return void
		 */
		private function autoload_options() {
			global $wpum_options;
			require_once WPUM_PLUGIN_DIR . 'includes/options.php';
			$wpum_options = wpum_get_settings();
		}

		/**
		 * Load plugins required files.
		 *
		 * @return void
		 */
		private function includes() {

			require_once WPUM_PLUGIN_DIR . 'includes/functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/permalinks.php';
			require_once WPUM_PLUGIN_DIR . 'includes/actions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/filters.php';
			require_once WPUM_PLUGIN_DIR . 'includes/assets.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-async-process.php';
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/abstract-wp-db-table.php';
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/abstract-wpum-db.php';
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/abstract-shortcode-generator.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-user-meta-custom-datastore.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table-field-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table-fields-groups.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table-registration-forms.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table-registration-forms-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-table-search-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-fields-groups.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-field-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-registration-forms.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-registration-form-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-database/class-wpum-db-search-fields.php';

			$this->setup_database_tables();

			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-html-elements.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/wpum-fields-functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/class-wpum-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/class-wpum-field-group.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/class-wpum-field.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/class-wpum-fields-query.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-forms/class-wpum-registration-form.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-emails/wpum-email-functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-emails/class-wpum-emails.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-avatars.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-prevent-password-change.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-template-loader.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-options-panel.php';
			( new WPUM_Options_Panel() )->init();

			require_once WPUM_PLUGIN_DIR . 'includes/wpum-forms/class-wpum-forms.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-widgets/class-wpum-login-form-widget.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-widgets/class-wpum-password-recovery-form-widget.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-widgets/class-wpum-recent-users.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-widgets/class-wpum-registration-form-widget.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-menus.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-directories/class-wpum-directories-editor.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-directories/wpum-directories-functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/widgets.php';

			// require_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/class-wpum-updates.php';
			require_once WPUM_PLUGIN_DIR . 'includes/wpum-upgrades/class-wpum-plugin-updates.php';

			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-getting-started.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-addons-page.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-notices.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-user-table.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-acf.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-admin/class-wpum-permalinks-settings.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-fields/class-wpum-fields-editor.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-forms/class-wpum-registration-forms-editor.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-emails/class-wpum-emails-list.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-updater/class-wpum-updater-settings.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-updater/free-plugins.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-button.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-login.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-login-link.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-logout-link.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-password.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-registration.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-my-account.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-profile.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-content-loggedin.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-content-users.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-content-roles.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-recently-registered.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-profile-card.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/class-wpum-shortcode-directory.php';
			}

			if ( defined( 'DOING_AJAX' ) || ( isset( $_GET['wpum_email_customizer'] ) && 'true' == $_GET['wpum_email_customizer'] ) ) {
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-emails/class-wpum-emails-customizer-scripts.php';
				require_once WPUM_PLUGIN_DIR . 'includes/wpum-emails/class-wpum-emails-customizer.php';
			}

			require_once WPUM_PLUGIN_DIR . 'includes/wpum-updater/class-wpum-license.php';

			require_once WPUM_PLUGIN_DIR . 'includes/install.php';

		}

		/**
		 * Setup all of the custom database tables
		 *
		 * This method invokes all of the classes for each custom database table,
		 * and returns them in an array for easier testing.
		 *
		 * In a normal request, this method is called extremely early in WPUM's load
		 * order, to ensure these tables have been created & upgraded before any
		 * other utility occurs on them (query, migration, etc...)
		 *
		 * @access public
		 * @return array
		 */
		private function setup_database_tables() {
			return array(
				'fields'                => new WPUM_DB_Table_Fields(),
				'fieldmeta'             => new WPUM_DB_Table_Field_Meta(),
				'fieldsgroups'          => new WPUM_DB_Table_Fields_Groups(),
				'registrationforms'     => new WPUM_DB_Table_Registration_Forms(),
				'registrationformsmeta' => new WPUM_DB_Table_Registration_Forms_Meta(),
				'searchfields'          => new WPUM_DB_Table_Search_Fields(),
			);
		}

		/**
		 * Hook in our actions and filters.
		 *
		 * @return void
		 */
		private function init_hooks() {
			register_activation_hook( WPUM_PLUGIN_FILE, 'wp_user_manager_install' );
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ), 0 );
			add_action( 'plugins_loaded', array( $this, 'init' ), 0 );
		}

		/**
		 * Load plugin textdomain.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'wpum', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}

		/**
		 * Hook into WordPress once all plugins are loaded.
		 *
		 * @return void
		 */
		public function init() {

			/**
			 * @todo document before_wpum_init
			 */
			do_action( 'before_wpum_init' );

			// Boot the custom routing library.
			Brain\Cortex::boot();

			// Start carbon fields and remove the sidebar manager scripts.
			\Carbon_Fields\Carbon_Fields::boot();
			$sidebar_manager = \Carbon_Fields\Carbon_Fields::resolve( 'sidebar_manager' );
			remove_action( 'admin_enqueue_scripts', array( $sidebar_manager, 'enqueue_scripts' ) );

			$this->notices                = TDP\WP_Notice::instance();
			$this->forms                  = WPUM_Forms::instance();
			$this->templates              = new WPUM_Template_Loader();
			$this->emails                 = new WPUM_Emails();
			$this->fields_groups          = new WPUM_DB_Fields_Groups();
			$this->fields                 = new WPUM_DB_Fields();
			$this->field_meta             = new WPUM_DB_Field_Meta();
			$this->registration_forms     = new WPUM_DB_Registration_Forms();
			$this->registration_form_meta = new WPUM_DB_Registration_Form_Meta();
			$this->elements               = new WPUM_HTML_Elements();
			$this->search_meta            = new WPUM_DB_Search_Fields();
			$this->async_process          = new WPUM_Async_Process();

			require_once WPUM_PLUGIN_DIR . 'includes/wpum-shortcodes/shortcodes.php';

			/**
			 * @todo document after_wpum_init
			 */
			do_action( 'after_wpum_init' );

		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @return void
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-user-manager' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @since 1.0.0
		 * @access protected
		 * @return void
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-user-manager' ), '1.0.0' );
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @since 1.0.0
		 * @return void
		 */
		private function setup_constants() {

			// Plugin version.
			if ( ! defined( 'WPUM_VERSION' ) ) {
				define( 'WPUM_VERSION', $this->version );
			}

			// Plugin Folder Path.
			if ( ! defined( 'WPUM_PLUGIN_DIR' ) ) {
				define( 'WPUM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'WPUM_PLUGIN_URL' ) ) {
				define( 'WPUM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			}

			// Plugin Root File.
			if ( ! defined( 'WPUM_PLUGIN_FILE' ) ) {
				define( 'WPUM_PLUGIN_FILE', __FILE__ );
			}

			// Plugin Slug.
			if ( ! defined( 'WPUM_SLUG' ) ) {
				define( 'WPUM_SLUG', plugin_basename( __FILE__ ) );
			}

		}

		/**
		 * Verify that the current environment is supported.
		 *
		 * @return boolean
		 */
		private function plugin_can_run() {

			require __DIR__ . '/vendor/autoload.php';

			$requirements_check = new WP_Requirements_Check(
				array(
					'title' => 'WP User Manager',
					'php'   => '5.5',
					'wp'    => '4.7',
					'file'  => __FILE__,
				)
			);

			return $requirements_check->passes();

		}

	}

endif; // End if class_exists check.

/**
 * Start WP User Manager.
 * The main function responsible for returning the one true WP User Manager instance to functions everywhere.
 *
 * @return WP_User_Manager
 */
function WPUM() {
	return WP_User_Manager::instance();
}

WPUM();
