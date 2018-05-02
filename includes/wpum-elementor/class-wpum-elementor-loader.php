<?php
/**
 * Handles integration with the Elementor page builder plugin.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that integration with Elementor.
 */
class WPUM_Elementor_Loader {

	/**
	 * Get thing started.
	 */
	public function __construct() {
		$this->includes();
		$this->init();
	}

	/**
	 * Load the required files for elementor.
	 *
	 * @return void
	 */
	private function includes() {
		require_once WPUM_PLUGIN_DIR . 'includes/wpum-elementor/class-wpum-card-document-type.php';
	}

	/**
	 * Hook into Elementor.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'elementor/documents/register', [ $this, 'register_document_types' ], 20 );
	}

	/**
	 * Register Elementor new document type.
	 *
	 * @param object $document
	 * @return void
	 */
	public function register_document_types( $document ) {
		$document->register_document_type( 'profile_card', \Elementor\Modules\Library\Documents\Profile_Card::get_class_full_name() );
	}

}

new WPUM_Elementor_Loader;
