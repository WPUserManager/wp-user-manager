<?php
/**
 * WPUM Template loader class..
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
 * Dynamic templates loader for WPUM.
 */
class WPUM_Template_Loader extends WPUM\Gamajo_Template_Loader {

	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'wpum';

	/**
	 * Directory name where templates should be found into the theme.
	 *
	 * @var string
	 */
	protected $theme_template_directory = 'wpum';

	/**
	 * Current plugin's root directory.
	 *
	 * @var string
	 */
	protected $plugin_directory = WPUM_PLUGIN_DIR;

	/**
	 * Directory name of where the templates are stored into the plugin.
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates';

}
