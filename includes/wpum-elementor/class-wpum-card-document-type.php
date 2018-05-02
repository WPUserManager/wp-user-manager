<?php
/**
 * Register a new document type for elementor.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/
namespace Elementor\Modules\Library\Documents;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Elementor profile card library document.
 *
 * Profile card library document handler class is responsible for
 * handling a document of a section type.
 * @since 2.0.0
 */
class Profile_Card extends Library_Document {

	/**
	 * Get document properties.
	 * Retrieve the document properties.
	 *
	 * @access public
	 * @static
	 * @return array Document properties.
	 */
	public static function get_properties() {
		$properties                 = parent::get_properties();
		$properties['library_view'] = 'list';
		$properties['group']        = 'blocks';

		return $properties;
	}

	/**
	 * Get document name.
	 * Retrieve the document name.
	 *
	 * @access public
	 * @return string Document name.
	 */
	public function get_name() {
		return 'profile_card';
	}

	/**
	 * Get document title.
	 *
	 * Retrieve the document title.
	 *
	 * @access public
	 * @static
	 * @return string Document title.
	 */
	public static function get_title() {
		return esc_html__( 'Profile Card' );
	}

}
