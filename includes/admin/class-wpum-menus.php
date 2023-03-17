<?php
/**
 * Handles the registration of custom fields for the menu items.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the settings for the menu items.
 */
class WPUM_Menus {

	/**
	 * Get things started.
	 */
	public function __construct() {

		if ( defined( 'WPUM_DISABLE_MENUS_CONTROLLER' ) && WPUM_DISABLE_MENUS_CONTROLLER === true ) {
			return;
		}

		add_action( 'carbon_fields_register_fields', array( $this, 'menu_settings' ) );
		add_action( 'admin_head', array( $this, 'cssjs' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'js' ) );
		add_filter( 'nav_menu_link_attributes', array( $this, 'set_nav_item_as_logout' ), 10, 3 );
		if ( ! is_admin() ) {
			add_filter( 'wp_get_nav_menu_items', array( $this, 'exclude_menu_items' ), 10, 3 );
		}

		add_action( 'wp_nav_menu_item_custom_fields', array( $this, 'nav_walker_overide_fix' ), 10, 4 );
	}

	/**
	 * Register menu settings.
	 *
	 * @return void
	 */
	public function menu_settings() {
		Container::make( 'nav_menu_item', 'Menu Settings' )
			->add_fields(
				array(
					Field::make( 'checkbox', 'convert_to_logout', esc_html__( 'Set as logout url', 'wp-user-manager' ) )
						->set_help_text( esc_html__( 'Enable to make this link a logout link.', 'wp-user-manager' ) )
						->set_classes( 'wpum-link-logout-toggle' ),
					Field::make( 'select', 'link_visibility', esc_html__( 'Display to:', 'wp-user-manager' ) )
					->add_options(
						array(
							''    => esc_html__( 'Everyone', 'wp-user-manager' ),
							'in'  => esc_html__( 'Logged in users', 'wp-user-manager' ),
							'out' => esc_html__( 'Logged out users', 'wp-user-manager' ),
						)
					)
					->set_classes( 'wpum-link-visibility-toggle' )
					->set_help_text( esc_html__( 'Set the visibility of this menu item.', 'wp-user-manager' ) ),
					Field::make( 'multiselect', 'link_roles', esc_html__( 'Select roles:', 'wp-user-manager' ) )
							->add_options( $this->get_roles() )
							->set_classes( 'wpum-link-visibility-roles' )
							->set_help_text( esc_html__( 'Select the roles that should see this menu item. Leave blank for all roles.', 'wp-user-manager' ) ),
				)
			);
	}

	/**
	 * Return an array containing user roles.
	 *
	 * @return array
	 */
	private function get_roles() {

		$roles = array();

		foreach ( wpum_get_roles( true, true ) as $role ) {
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
			#menu-management .carbon-field.carbon-checkbox {padding-left:0px !important;}
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
		if ( 'nav-menus' === $screen->base ) {
			wp_enqueue_script( 'wpum-menu-editor', WPUM_PLUGIN_URL . '/assets/js/admin/admin-menus.min.js', false, WPUM_VERSION, true );
		}
	}

	/**
	 * @param object $item
	 *
	 * @return false|mixed
	 */
	protected function is_nav_item_logout( $item ) {
		if ( ! apply_filters( 'wpum_pre_is_nav_item_logout', true, $item ) ) {
			return false;
		}

		return \WPUM\carbon_get_nav_menu_item_meta( $item->ID, 'convert_to_logout' );
	}

	/**
	 * Modify a nav menu item url to a logout url if the option is enabled.
	 *
	 * @param array  $atts
	 * @param object $item
	 * @param array  $args
	 * @return array
	 */
	public function set_nav_item_as_logout( $atts, $item, $args ) {

		$is_logout = $this->is_nav_item_logout( $item );

		if ( $is_logout ) {
			$atts['href'] = wp_logout_url();
		}

		return $atts;

	}

	/**
	 * Determine if the menu item should be visible or not.
	 *
	 * @param array $items
	 * @param array $menu
	 * @param array $args
	 *
	 * @return array
	 */
	public function exclude_menu_items( $items, $menu, $args ) {

		foreach ( $items as $key => $item ) {

			$status    = \WPUM\carbon_get_nav_menu_item_meta( $item->ID, 'link_visibility' );
			$roles     = \WPUM\carbon_get_nav_menu_item_meta( $item->ID, 'link_roles' );
			$is_logout = $this->is_nav_item_logout( $item );
			$visible   = true;

			switch ( $status ) {
				case 'in':
					$visible = is_user_logged_in();
					if ( is_array( $roles ) && ! empty( $roles ) && $visible ) {
						$user = wp_get_current_user();

						if ( ! array_intersect( (array) $user->roles, $roles ) ) {
							$visible = false;
						}

						if ( current_user_can( 'administrator' ) && apply_filters( 'wpum_menu_restriction_allow_admins', true ) ) {
							$visible = true;
						}
					}
					break;
				case 'out':
					$visible = ! is_user_logged_in();
					break;
			}
			// Now exclude item if not visible.
			if ( ! $visible && ! $is_logout ) {
				unset( $items[ $key ] );
			}

			if ( $is_logout && ! is_user_logged_in() ) {
				unset( $items[ $key ] );
			}
		}

		return $items;

	}

	/**
	 * When other themes or plugins extend the Walker_Nav_Menu_Edit class, ours won't get called
	 * Use the WP 5.4+ hook to inject what Carbon Fields needs for the menu item settings.
	 *
	 * @param string        $id
	 * @param WP_Post       $item
	 * @param int           $depth
	 * @param stdClass|null $args
	 */
	public function nav_walker_overide_fix( $id, $item, $depth, $args ) {
		if ( is_a( $args->walker, 'WPUM\\Carbon_Fields\\Walker\\Nav_Menu_Item_Edit_Walker' ) ) {
			return;
		}

		$flag = '<!--CarbonFields-->';

		ob_start();
		do_action( 'carbon_fields_print_nav_menu_item_container_fields', $item, '', $depth, $args, $id );
		echo wp_kses_post( $flag );
		$output = ob_get_clean();

		echo wp_kses_post( $output );
	}

}

new WPUM_Menus();
