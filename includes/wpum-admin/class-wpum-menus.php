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
		add_action( 'load-nav-menus.php', [ $this, 'styling' ] );
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
				Field::make( 'select', 'crb_content_align', esc_html__( 'Display to:' ) )
					->add_options( array(
						''    => esc_html__( 'Everyone' ),
						'in'  => esc_html__( 'Logged in users' ),
						'out' => esc_html__( 'Logged out users' ),
					) )
					->set_help_text( esc_html__( 'Set the visibility of this menu item.' ) ),
			) );
	}

	/**
	 * Adjust styling of the menu settings.
	 *
	 * @return void
	 */
	public function styling() {
		?>
		<style>
			.carbon-field.carbon-checkbox {padding-left:0px !important;}
		</style>
		<?php
	}

}

new WPUM_Menus;
