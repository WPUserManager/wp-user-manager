<?php
/**
 * Handles the registration of custom fields for the menu items.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define the settings for the menu items.
 */
class WPUM_Menus {

	/**
	 * Get things started.
	 */
	public function __construct() {
		add_action( 'carbon_fields_register_fields', [ $this, 'menu_settings' ] );
		add_action( 'load-nav-menus.php', [ $this, 'cssjs' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'js' ] );
	}

	/**
	 * Register menu settings.
	 *
	 * @return void
	 */
	public function menu_settings() {
		Container::make( 'nav_menu_item', 'Menu Settings' )
			->add_fields( array(
				Field::make( 'checkbox', 'convert_to_logout', esc_html__( 'Set as logout url' ) )
					->set_help_text( esc_html__( 'Enable to make this link a logout link.' ) ),
				Field::make( 'select', 'link_visibility', esc_html__( 'Display to:' ) )
					->add_options( array(
						''    => esc_html__( 'Everyone' ),
						'in'  => esc_html__( 'Logged in users' ),
						'out' => esc_html__( 'Logged out users' ),
					) )
					->set_classes( 'wpum-link-visibility-toggle' )
					->set_help_text( esc_html__( 'Set the visibility of this menu item.' ) ),
				Field::make( 'multiselect', 'link_roles', esc_html__( 'Select roles:' ) )
					->add_options( $this->get_roles() )
					->set_classes( 'wpum-link-visibility-roles' )
					->set_help_text( esc_html__( 'Select the roles that should see this menu item. Leave blank for all roles.' ) )
			) );
	}

	/**
	 * Return an array containing user roles.
	 *
	 * @return array
	 */
	private function get_roles() {

		$roles = [];

		foreach( wpum_get_roles( true ) as $role ) {
			$roles[ $role['value'] ] = $role['label'];
		}

		return $roles;

	}

	/**
	 * Adjust styling of the menu settings.
	 *
	 * @return void
	 */
	public function cssjs() {
		?>
		<style>
			.carbon-field.carbon-checkbox {padding-left:0px !important;}
			.wpum-link-visibility-roles {display:none};
		</style>
		<?php
	}

	/**
	 * Add custom js file to handle hide/show of the roles selector.
	 *
	 * @return void
	 */
	public function js() {
		$screen = get_current_screen();
		if( $screen->base == 'nav-menus' ) {
			wp_enqueue_script( 'wpum-menu-editor', WPUM_PLUGIN_URL . '/assets/js/admin/admin-menus.min.js', false, WPUM_VERSION, true );
		}
	}

}

new WPUM_Menus;
