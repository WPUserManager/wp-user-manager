<?php
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
 * @author         WP User Manager
 * @version        2.3.6
 * @copyright  (c) 2020 WP User Manager
 * @license        http://www.gnu.org/licenses/gpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE
 * @package        wp-user-manager
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
		 * Plugin file path
		 *
		 * @var string
		 */
		protected $plugin_file;

		/**
		 * Plugin version
		 *
		 * @var string
		 */
		protected $version;

		/**
		 * WPUM Instance.
		 *
		 * @var WP_User_Manager the WPUM Instance
		 */
		protected static $instance;

		/**
		 * Holds the admin notice creation helper class.
		 *
		 * @var TDP\WP_Notice
		 */
		public $notices;

		/**
		 * Store the template loader object.
		 *
		 * @var WPUM_Template_Loader
		 */
		public $templates;

		/**
		 * Store all forms.
		 *
		 * @var WPUM_Forms
		 */
		public $forms;

		/**
		 * The email templates handler.
		 *
		 * @var WPUM_Emails
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
		 * @var WPUM_HTML_Elements
		 */
		public $elements;

		/**
		 * @var WPUM_Fields
		 */
		public $field_types;

		/**
		 * @var WPUM_Directories_Editor
		 */
		public $directories_editor;

		/**
		 * @var array
		 */
		protected $aliased_classes = array();

		/**
		 * Main WPUM Instance.
		 *
		 * Ensures that only one instance of WPUM exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @param string $plugin_file
		 * @param string $plugin_version
		 *
		 * @return WP_User_Manager
		 */
		public static function instance( $plugin_file, $plugin_version ) {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self( $plugin_file, $plugin_version );
			}

			return self::$instance;
		}

		/**
		 * Get things up and running.
		 *
		 * @param string $plugin_file
		 * @param string $plugin_version
		 */
		public function __construct( $plugin_file, $plugin_version ) {
			$this->plugin_file = $plugin_file;
			$this->version     = $plugin_version;

			$this->autoload();

			// Verify the plugin can run first. If not, disable the plugin automagically.
			if ( ! $this->plugin_can_run() ) {
				return;
			}

			$this->setup_constants();

			$this->autoload_options();
			$this->includes();

			$this->addons_can_run();

			$this->init_hooks();
		}

		/**
		 * Autoload composer and other required classes.
		 *
		 * @return void
		 */
		private function autoload() {
			if ( file_exists( dirname( $this->plugin_file ) . '/vendor-dist/scoper-autoload.php' ) ) {
				require_once dirname( $this->plugin_file ) . '/vendor-dist/scoper-autoload.php';
				\spl_autoload_register( array( $this, 'ensure_addon_class_alias' ), true, true );
				require_once dirname( $this->plugin_file ) . '/includes/functions-scoped.php';
			} elseif ( file_exists( dirname( $this->plugin_file ) . '/vendor/autoload.php' ) ) {
				require_once dirname( $this->plugin_file ) . '/vendor/autoload.php';
				\spl_autoload_register( array( $this, 'ensure_class_alias' ), true, true );
			}
		}

		/**
		 * Ensure WPUM addon calls to core dependencies still work when calling unscoped versions
		 *
		 * @param string $class Class to create alias for.
		 *
		 * @return void
		 */
		public function ensure_addon_class_alias( $class ) {
			if ( strpos( $class, 'WPUM\\' ) === 0 ) {
				// Class is already scoped
				return;
			}

			if ( class_exists( $class ) ) {
				// Class exists already
				return;
			}

			$allowed = array(
				'Gamajo_Template_Loader',
				'Carbon_Fields\Field',
				'Carbon_Fields\Container',
				'Carbon_Fields\Carbon_Fields',
				'Brain\Cortex\Route\RedirectRoute',
				'Brain\Cortex\Route\QueryRoute',
				'TDP\OptionsKit',
			);

			if ( ! in_array( $class, apply_filters( 'wpum_allowed_classes_to_alias_to_scoped_class', $allowed ), true ) ) {
				return;
			}

			$scoped = 'WPUM\\' . $class;
			if ( ! class_exists( $scoped ) ) {
				// Scoped version of the class doesn't exist
				return;
			}

			if ( in_array( $class, $this->aliased_classes, true ) ) {
				return;
			}

			$this->aliased_classes[] = $class;

			class_alias( $scoped, $class );
		}

		/**
		 * Makes sure a class alias is created when a base class exists.
		 *
		 * @param string $class Class to create alias for.
		 *
		 * @return void
		 */
		public function ensure_class_alias( $class ) {
			// If the namespace beings with the dependency class prefix, make an alias for regular class.
			if ( strpos( $class, 'WPUM' ) !== 0 ) {
				return;
			}
			$base = substr( $class, ( strlen( 'WPUM' ) + 1 ) );
			if ( ! class_exists( $base ) ) {
				return;
			}

			class_alias( $base, $class );
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
			require_once WPUM_PLUGIN_DIR . 'includes/forms/trait-wpum-account.php';
			require_once WPUM_PLUGIN_DIR . 'includes/permalinks.php';
			require_once WPUM_PLUGIN_DIR . 'includes/actions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/filters.php';
			require_once WPUM_PLUGIN_DIR . 'includes/assets.php';
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/class-wpum-wp-db-table.php';
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/class-wpum-db.php';
			require_once WPUM_PLUGIN_DIR . 'includes/abstracts/class-wpum-shortcode-generator.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-user-meta-custom-datastore.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-addon-check.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-field-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-fields-groups.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-registration-forms.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-registration-forms-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-search-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-stripe-invoices.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-table-stripe-subscriptions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-fields-groups.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-field-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-registration-forms.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-registration-form-meta.php';
			require_once WPUM_PLUGIN_DIR . 'includes/database/class-wpum-db-search-fields.php';

			$this->setup_database_tables();

			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-html-elements.php';
			require_once WPUM_PLUGIN_DIR . 'includes/fields/wpum-fields-functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/fields/class-wpum-fields.php';
			require_once WPUM_PLUGIN_DIR . 'includes/fields/class-wpum-field-group.php';
			require_once WPUM_PLUGIN_DIR . 'includes/fields/class-wpum-field.php';
			require_once WPUM_PLUGIN_DIR . 'includes/fields/class-wpum-fields-query.php';
			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-registration-form.php';
			require_once WPUM_PLUGIN_DIR . 'includes/emails/wpum-email-functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/emails/class-wpum-emails.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-avatars.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-prevent-password-change.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-template-loader.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-options-panel.php';
			( new WPUM_Options_Panel() )->init();

			require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-forms.php';
			require_once WPUM_PLUGIN_DIR . 'includes/widgets/class-wpum-login-form-widget.php';
			require_once WPUM_PLUGIN_DIR . 'includes/widgets/class-wpum-password-recovery.php';
			require_once WPUM_PLUGIN_DIR . 'includes/widgets/class-wpum-recently-registered-users.php';
			require_once WPUM_PLUGIN_DIR . 'includes/widgets/class-wpum-registration-form-widget.php';
			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-menus.php';
			require_once WPUM_PLUGIN_DIR . 'includes/directories/class-wpum-directories-editor.php';
			require_once WPUM_PLUGIN_DIR . 'includes/directories/wpum-directories-functions.php';
			require_once WPUM_PLUGIN_DIR . 'includes/widgets.php';
			require_once WPUM_PLUGIN_DIR . 'includes/integrations/elementor/class-wpum-elementor-loader.php';

			require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-plugin-updates.php';

			if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
				require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-getting-started.php';
				require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-addons-page.php';
				require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-admin-notices.php';
				require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-user-table.php';
				require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-addon-acf.php';
				require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-permalinks-settings.php';
				require_once WPUM_PLUGIN_DIR . 'includes/fields/class-wpum-fields-editor.php';
				require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-registration-forms-editor.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/class-wpum-role.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/class-wpum-capability.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/class-wpum-capability-group.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/class-wpum-collection.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/class-wpum-roles.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/functions.php';
				require_once WPUM_PLUGIN_DIR . 'includes/roles/class-wpum-roles-editor.php';
				require_once WPUM_PLUGIN_DIR . 'includes/emails/class-wpum-emails-list.php';
				require_once WPUM_PLUGIN_DIR . 'includes/updates/class-wpum-updater-settings.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-button.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-login.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-login-link.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-logout-link.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-password.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-registration.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-my-account.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-profile.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-content-loggedin.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-content-loggedout.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-content-users.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-content-roles.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-recently-registered.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-profile-card.php';
				require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/class-wpum-shortcode-directory.php';
			}

			require_once WPUM_PLUGIN_DIR . 'includes/install.php';

			$email_customizer = filter_input( INPUT_GET, 'wpum_email_customizer', FILTER_SANITIZE_STRING );
			if ( defined( 'DOING_AJAX' ) || 'true' === $email_customizer ) {
				require_once WPUM_PLUGIN_DIR . 'includes/emails/class-wpum-emails-customizer-scripts.php';
				require_once WPUM_PLUGIN_DIR . 'includes/emails/class-wpum-emails-customizer.php';
			}

			require_once WPUM_PLUGIN_DIR . 'includes/updates/class-wpum-license.php';
			require_once WPUM_PLUGIN_DIR . 'includes/updates/free-plugins.php';

			require_once WPUM_PLUGIN_DIR . 'includes/compatibility/oceanwp.php';

			\WPUM\WPUM_Blocks::get_instance();
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

			( new WPUM_Plugin_Updates() )->init();

			( new WPUM_Elementor_Loader() )::get_instance();

			$this->field_types = new WPUM_Fields();
			$this->field_types->init();

			$this->directories_editor = new WPUM_Directories_Editor();
			$this->directories_editor->init();
		}

		/**
		 * Load plugin textdomain.
		 *
		 * @return void
		 */
		public function load_textdomain() {
			load_plugin_textdomain( 'wp-user-manager', false, basename( dirname( $this->plugin_file ) ) . '/languages' );
		}

		/**
		 * Hook into WordPress once all plugins are loaded.
		 *
		 * @return void
		 */
		public function init() {

			do_action( 'before_wpum_init' );

			// Boot Stripe code
			( new \WPUserManager\Stripe\Stripe() )->init();

			// Boot the custom routing library.
			\WPUM\Brain\Cortex::boot();

			// Start carbon fields and remove the sidebar manager scripts.
			\WPUM\Carbon_Fields\Carbon_Fields::boot();
			$sidebar_manager = \WPUM\Carbon_Fields\Carbon_Fields::resolve( 'sidebar_manager' );
			remove_action( 'admin_enqueue_scripts', array( $sidebar_manager, 'enqueue_scripts' ) );

			$this->notices                = \WPUM\TDP\WP_Notice::instance();
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

			require_once WPUM_PLUGIN_DIR . 'includes/shortcodes/shortcodes.php';

			do_action( 'after_wpum_init' );

		}

		/**
		 * Throw error on object clone
		 *
		 * The whole idea of the singleton design pattern is that there is a single
		 * object therefore, we don't want the object to be cloned.
		 *
		 * @return void
		 * @since  1.0.0
		 * @access protected
		 */
		public function __clone() {
			// Cloning instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-user-manager' ), '1.0.0' );
		}

		/**
		 * Disable unserializing of the class
		 *
		 * @return void
		 * @since  1.0.0
		 * @access protected
		 */
		public function __wakeup() {
			// Unserializing instances of the class is forbidden
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wp-user-manager' ), '1.0.0' );
		}

		/**
		 * Setup plugin constants
		 *
		 * @access private
		 * @return void
		 * @since  1.0.0
		 */
		private function setup_constants() {

			// Plugin version.
			if ( ! defined( 'WPUM_VERSION' ) ) {
				define( 'WPUM_VERSION', $this->version );
			}

			// Plugin Folder Path.
			if ( ! defined( 'WPUM_PLUGIN_DIR' ) ) {
				define( 'WPUM_PLUGIN_DIR', plugin_dir_path( $this->plugin_file ) );
			}

			// Plugin Folder URL.
			if ( ! defined( 'WPUM_PLUGIN_URL' ) ) {
				define( 'WPUM_PLUGIN_URL', plugin_dir_url( $this->plugin_file ) );
			}

			// Plugin Root File.
			if ( ! defined( 'WPUM_PLUGIN_FILE' ) ) {
				define( 'WPUM_PLUGIN_FILE', $this->plugin_file );
			}

			// Plugin Slug.
			if ( ! defined( 'WPUM_SLUG' ) ) {
				define( 'WPUM_SLUG', plugin_basename( $this->plugin_file ) );
			}

		}

		/**
		 * Verify that the current environment is supported.
		 *
		 * @return boolean
		 */
		private function plugin_can_run() {
			$requirements_check = new \WPUM\WP_Requirements_Check(
				array(
					'title' => 'WP User Manager',
					'php'   => '5.5',
					'wp'    => '4.7',
					'file'  => $this->plugin_file,
				)
			);

			return $requirements_check->passes();
		}

		/**
		 * Ensure the minimum required versions of addons are installed.
		 * Prevents fatals when addons are out of date with core.
		 */
		protected function addons_can_run() {
			$addons = array(
				array(
					'title'       => 'WPUM Groups',
					'min_version' => '1.2.3',
					'file'        => 'wpum-groups/wpum-groups.php',
				),
				array(
					'title'       => 'WPUM Social Login',
					'min_version' => '2.0.9',
					'file'        => 'wpum-social-login/wpum-social-login.php',
				),
			);

			foreach ( $addons as $addon ) {
				$addon['file'] = WP_PLUGIN_DIR . '/' . $addon['file'];
				( new WPUM_Addon_Check( $addon ) )->passes();
			}
		}

	}

endif; // End if class_exists check.
