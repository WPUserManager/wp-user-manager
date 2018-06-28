<?php

namespace Carbon_Fields\Loader;

use Carbon_Fields\Container\Repository as ContainerRepository;
use Carbon_Fields\Exception\Incorrect_Syntax_Exception;
use Carbon_Fields\Helper\Helper;
use Carbon_Fields\Libraries\Sidebar_Manager\Sidebar_Manager;
use Carbon_Fields\Pimple\Container as PimpleContainer;
use Carbon_Fields\Service\Legacy_Storage_Service_v_1_5;
use Carbon_Fields\Service\Meta_Query_Service;
use Carbon_Fields\Service\REST_API_Service;

/**
 * Loader and main initialization
 */
class Loader {

	protected $sidebar_manager;

	protected $container_repository;

	public function __construct( Sidebar_Manager $sidebar_manager, ContainerRepository $container_repository ) {
		$this->sidebar_manager = $sidebar_manager;
		$this->container_repository = $container_repository;
	}

	/**
	 * Hook the main Carbon Fields initialization functionality.
	 */
	public function boot() {
		if ( ! defined( 'ABSPATH' ) ) {
			throw new \Exception( 'Carbon Fields cannot be booted outside of a WordPress environment.' );
		}

		if ( did_action( 'init' ) ) {
			throw new \Exception( 'Carbon Fields must be booted before the "init" WordPress action has fired.' );
		}

		include_once( dirname( dirname( __DIR__ ) ) . '/config.php' );
		include_once( \Carbon_Fields\DIR . '/core/functions.php' );

		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ), 9999 );
		add_action( 'init', array( $this, 'trigger_fields_register' ), 0 );
		add_action( 'carbon_fields_fields_registered', array( $this, 'initialize_containers' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_browser' ), 0 );
		add_action( 'admin_print_footer_scripts', array( $this, 'enqueue_scripts' ), 0 );
		add_action( 'admin_print_footer_scripts', array( $this, 'print_json_data_script' ), 9 );
		add_action( 'admin_print_footer_scripts', array( $this, 'print_bootstrap_js' ), 100 );
		add_action( 'edit_form_after_title', array( $this, 'add_carbon_fields_meta_box_contexts' ) );

		# Enable the legacy storage service
		\Carbon_Fields\Carbon_Fields::service( 'legacy_storage' )->enable();

		# Enable the meta query service
		\Carbon_Fields\Carbon_Fields::service( 'meta_query' )->enable();

		# Enable the REST API service
		\Carbon_Fields\Carbon_Fields::service( 'rest_api' )->enable();

		# Initialize sidebar manager
		$this->sidebar_manager->boot();
	}

	/**
	 * Load the plugin textdomain.
	 */
	public function load_textdomain() {
		$dir = \Carbon_Fields\DIR . '/languages/';
		$domain = 'carbon-fields';
		$locale = get_locale();
		$path = $dir . $domain . '-' . $locale . '.mo';
		load_textdomain( $domain, $path );
	}

	/**
	 * Register containers and fields.
	 */
	public function trigger_fields_register() {
		try {
			do_action( 'carbon_fields_register_fields' );
			do_action( 'carbon_fields_fields_registered' );
		} catch ( Incorrect_Syntax_Exception $e ) {
			$callback = '';
			foreach ( $e->getTrace() as $trace ) {
				$callback .= '<br/>' . ( isset( $trace['file'] ) ? $trace['file'] . ':' . $trace['line'] : $trace['function'] . '()' );
			}
			wp_die( '<h3>' . $e->getMessage() . '</h3><small>' . $callback . '</small>' );
		}
	}

	/**
	 * Initialize containers.
	 */
	public function initialize_containers() {
		$this->container_repository->initialize_containers();
	}

	/**
	 * Initialize the media browser.
	 */
	public function enqueue_media_browser() {
		wp_enqueue_media();
	}

	/**
	 * Initialize main scripts
	 */
	public function enqueue_scripts() {
		$locale = get_locale();
		$short_locale = substr( $locale, 0, 2 );
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_style( 'carbon-fields-core', \Carbon_Fields\URL . '/assets/dist/carbon.css', array(), \Carbon_Fields\VERSION );

		wp_enqueue_script( 'carbon-fields-vendor', \Carbon_Fields\URL . '/assets/dist/carbon.vendor' . $suffix . '.js', array( 'jquery' ), \Carbon_Fields\VERSION );
		wp_enqueue_script( 'carbon-fields-core', \Carbon_Fields\URL . '/assets/dist/carbon.core' . $suffix . '.js', array( 'carbon-fields-vendor', 'quicktags', 'editor' ), \Carbon_Fields\VERSION );
		wp_enqueue_script( 'carbon-fields-boot', \Carbon_Fields\URL . '/assets/dist/carbon.boot' . $suffix . '.js', array( 'carbon-fields-core' ), \Carbon_Fields\VERSION );

		wp_localize_script( 'carbon-fields-vendor', 'carbonFieldsConfig', apply_filters( 'carbon_fields_config', array(
			'compactInput' => \Carbon_Fields\COMPACT_INPUT,
			'compactInputKey' => \Carbon_Fields\COMPACT_INPUT_KEY,
		) ) );
		wp_localize_script( 'carbon-fields-vendor', 'carbonFieldsL10n', apply_filters( 'carbon_fields_l10n', array(
			'locale' => $locale,
			'shortLocale' => $short_locale,

			'container' => array(
				'pleaseFillTheRequiredFields' => __( 'Please fill out all required fields highlighted below.', 'wp-user-manager' ),
				'changesMadeSaveAlert' => __( 'The changes you made will be lost if you navigate away from this page.', 'wp-user-manager' ),
			),

			'field' => array(
				'geocodeZeroResults' => __( 'The address could not be found. ', 'wp-user-manager' ),
				'geocodeNotSuccessful' => __( 'Geocode was not successful for the following reason: ', 'wp-user-manager' ),
				'mapLocateAddress' => __( 'Locate address on the map:', 'wp-user-manager' ),
				'minNumItemsNotReached' => __( 'Minimum number of items not reached (%s items)', 'wp-user-manager' ),
				'maxNumItemsReached' => __( 'Maximum number of items reached (%s items)', 'wp-user-manager' ),

				'complexNoRows' => __( 'There are no %s yet. Click <a href="#">here</a> to add one.', 'wp-user-manager' ),
				'complexMinNumRowsNotReached' => __( 'Minimum number of rows not reached (%1$d %2$s)', 'wp-user-manager' ),
				'complexMaxNumRowsExceeded' => __( 'Maximum number of rows exceeded (%1$d %2$s)', 'wp-user-manager' ),
				'complexAddButton' => _x( 'Add %s', 'Complex field', 'wp-user-manager' ),
				'complexCloneButton' => _x( 'Clone', 'Complex field', 'wp-user-manager' ),
				'complexRemoveButton' => _x( 'Remove', 'Complex field', 'wp-user-manager' ),
				'complexCollapseExpandButton' => _x( 'Collapse/Expand', 'Complex field', 'wp-user-manager' ),
				'complexCollapseAllButton' => _x( 'Collapse All', 'Complex field groups', 'wp-user-manager' ),
				'complexExpandAllButton' => _x( 'Expand All', 'Complex field groups', 'wp-user-manager' ),

				'messageFormValidationFailed' => __( 'Please fill out all fields correctly. ', 'wp-user-manager' ),
				'messageRequiredField' => __( 'This field is required. ', 'wp-user-manager' ),
				'messageChooseOption' => __( 'Please choose an option. ', 'wp-user-manager' ),

				'enterNameOfNewSidebar' => __( 'Please enter the name of the new sidebar:', 'wp-user-manager' ),

				'selectTime' => __( 'Select Time', 'wp-user-manager' ),
				'selectDate' => __( 'Select Date', 'wp-user-manager' ),

				'associationSelectedItem' => __( '%1$d selected item', 'wp-user-manager' ),
				'associationSelectedItems' => __( '%1$d selected items', 'wp-user-manager' ),
				'associationSelectedItemOutOf' => __( '%1$d selected item out of %2$d', 'wp-user-manager' ),
				'associationSelectedItemsOutOf' => __( '%1$d selected items out of %2$d', 'wp-user-manager' ),

				'colorSelectColor' => __( 'Select a color', 'wp-user-manager' ),

				'noOptions' => __( 'No options.', 'wp-user-manager' ),

				'setShowAll' => __( 'Show all options', 'wp-user-manager' ),

				'searchPlaceholder' => __( 'Search...', 'wp-user-manager' ),

				'editAttachmentUrl' => _x( 'URL', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentTitle' => _x( 'Title', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentArtist' => _x( 'Artist', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentAlbum' => _x( 'Album', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentCaption' => _x( 'Caption', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentAlt' => _x( 'Alt Text', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentDescription' => _x( 'Description', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentClose' => _x( 'Close', 'WordPress media attachment', 'wp-user-manager' ),
				'editAttachmentSave' => _x( 'Save', 'WordPress media attachment', 'wp-user-manager' ),

				'oembedNotFound' => _x( 'Not Found', 'oEmbed field', 'wp-user-manager' ),

				'mediaGalleryNoFiles' => __( 'No files selected.', 'wp-user-manager' ),
				'mediaGalleryButtonLabel' => __( 'Add File', 'wp-user-manager' ),
				'mediaGalleryBrowserTitle' => _x( 'Files', 'WordPress Media Browser', 'wp-user-manager' ),
				'mediaGalleryBrowserButtonLabel' => _x( 'Select File', 'WordPress Media Browser', 'wp-user-manager' ),

				'fileButtonLabel' => __( 'Select File', 'wp-user-manager' ),
				'fileBrowserTitle' => _x( 'Files', 'WordPress Media Browser', 'wp-user-manager' ),
				'fileBrowserButtonLabel' => _x( 'Select File', 'WordPress Media Browser', 'wp-user-manager' ),

				'imageButtonLabel' => __( 'Select Image', 'wp-user-manager' ),
				'imageBrowserTitle' => _x( 'Images', 'WordPress Media Browser', 'wp-user-manager' ),
				'imageBrowserButtonLabel' => _x( 'Select Image', 'WordPress Media Browser', 'wp-user-manager' ),
			),
		) ) );
	}

	/**
	 * Add custom meta box contexts
	 */
	public function add_carbon_fields_meta_box_contexts() {
		global $post, $wp_meta_boxes;

		$context = 'carbon_fields_after_title';
		foreach ( $wp_meta_boxes as $post_type => $meta_boxes ) {
			if ( empty( $meta_boxes[ $context ] ) ) {
				continue;
			}
			do_meta_boxes( get_current_screen(), $context, $post );
			unset( $wp_meta_boxes[ $post_type ][ $context ] );
		}
	}

	/**
	 * Retrieve containers and sidebars for use in the JS.
	 *
	 * @return array $carbon_data
	 */
	public function get_json_data() {
		global $pagenow;

		$carbon_data = array(
			'containers' => array(),
			'sidebars' => array(),
			'pagenow' => $pagenow,
		);

		$containers = $this->container_repository->get_active_containers();

		foreach ( $containers as $container ) {
			$container_data = $container->to_json( true );

			$carbon_data['containers'][] = $container_data;
		}

		$carbon_data['sidebars'] = Helper::get_active_sidebars();

		return $carbon_data;
	}

	/**
	 * Print the carbon JSON data script.
	 */
	public function print_json_data_script() {
		?>
<script type="text/javascript">
<!--//--><![CDATA[//><!--
var carbon_json = <?php echo wp_json_encode( $this->get_json_data() ); ?>;
//--><!]]>
</script>
		<?php
	}

	/**
	 * Print the bootstrap code for the fields.
	 */
	public function print_bootstrap_js() {
		?>
		<script type="text/javascript">
			if (window['carbon.boot'] && typeof window['carbon.boot'].default === 'function') {
				window['carbon.boot'].default();
			}
		</script>
		<?php
	}
}
