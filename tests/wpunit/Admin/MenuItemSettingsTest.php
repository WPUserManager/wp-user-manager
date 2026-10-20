<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Menu item visibility and logout settings.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/255
 * @see https://github.com/WPUserManager/wp-user-manager/issues/355
 */
class MenuItemSettingsTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	private $menu_id;

	public function _setUp() {
		parent::_setUp();
		$this->menu_id = wp_create_nav_menu( 'WPUM test menu ' . wp_generate_password( 6, false ) );
	}

	/**
	 * Add a custom link to the test menu.
	 *
	 * @param array $meta Raw post meta to store on the item.
	 *
	 * @return int Menu item ID.
	 */
	private function add_item( $meta = array() ) {
		$item_id = wp_update_nav_menu_item( $this->menu_id, 0, array(
			'menu-item-title'  => 'Link',
			'menu-item-url'    => 'https://example.com/',
			'menu-item-status' => 'publish',
		) );

		foreach ( $meta as $key => $value ) {
			update_post_meta( $item_id, $key, $value );
		}

		return $item_id;
	}

	/**
	 * Load the menu items as WordPress does, which fills the post meta cache.
	 *
	 * @return array
	 */
	private function get_items() {
		remove_all_filters( 'wp_get_nav_menu_items' );

		return wp_get_nav_menu_items( $this->menu_id );
	}

	public function test_item_with_non_numeric_id_is_left_alone() {
		global $wpdb;

		// Polylang's language switcher items have IDs like this.
		$item     = (object) array(
			'ID'    => '16511-es',
			'title' => 'Español',
			'url'   => 'https://example.com/es/',
		);
		$menus    = new WPUM_Menus();
		$queries  = $wpdb->num_queries;
		$filtered = $menus->exclude_menu_items( array( $item ), null, array() );
		$atts     = $menus->set_nav_item_as_logout( array( 'href' => $item->url ), $item, array() );

		$this->assertCount( 1, $filtered );
		$this->assertSame( 'https://example.com/es/', $atts['href'] );
		$this->assertSame( $queries, $wpdb->num_queries, 'No query should run for a non-numeric item ID.' );
	}

	public function test_items_without_settings_run_no_queries() {
		global $wpdb;

		$this->add_item();
		$this->add_item();
		$items = $this->get_items();

		$menus    = new WPUM_Menus();
		$queries  = $wpdb->num_queries;
		$filtered = $menus->exclude_menu_items( $items, null, array() );
		foreach ( $filtered as $item ) {
			$menus->set_nav_item_as_logout( array( 'href' => $item->url ), $item, array() );
		}

		$this->assertCount( 2, $filtered );
		$this->assertSame( $queries, $wpdb->num_queries, 'Items with no WPUM settings should not query the database.' );
	}

	public function test_logged_out_only_item_is_hidden_for_logged_in_users() {
		$this->add_item( array( '_link_visibility' => 'out' ) );
		$this->add_item();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$filtered = ( new WPUM_Menus() )->exclude_menu_items( $this->get_items(), null, array() );

		$this->assertCount( 1, $filtered );
	}

	public function test_logout_item_gets_the_logout_url() {
		$this->add_item( array( '_convert_to_logout' => 'yes' ) );
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$menus = new WPUM_Menus();
		$items = $menus->exclude_menu_items( $this->get_items(), null, array() );
		$item  = reset( $items );
		$atts  = $menus->set_nav_item_as_logout( array( 'href' => $item->url ), $item, array() );

		$this->assertStringContainsString( 'action=logout', $atts['href'] );
	}

	public function test_settings_are_loaded_once_per_item() {
		global $wpdb;

		$this->add_item( array( '_link_visibility' => 'in' ) );
		$items = $this->get_items();
		$menus = new WPUM_Menus();

		$menus->exclude_menu_items( $items, null, array() );
		$queries = $wpdb->num_queries;
		$menus->exclude_menu_items( $items, null, array() );
		$menus->set_nav_item_as_logout( array( 'href' => '' ), reset( $items ), array() );

		$this->assertSame( $queries, $wpdb->num_queries, 'Settings should be cached for the request.' );
	}
}
