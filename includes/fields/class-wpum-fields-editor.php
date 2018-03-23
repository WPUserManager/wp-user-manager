<?php
/**
 * Handles all the custom fields related functionalities in the backend.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The class that handles the fields editor.
 */
class WPUM_Fields_Editor {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_ajax_wpum_update_fields_groups_order', [ $this, 'update_groups_order' ] );
		add_action( 'wp_ajax_wpum_update_fields_group', [ $this, 'update_group' ] );
	}

	/**
	 * Add new menu page to the "Users" menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Fields Editor' ),
			esc_html__( 'Custom fields' ),
			'manage_options',
			'wpum-custom-fields',
			[ $this, 'display_fields_editor' ]
		);
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if( $screen->base == 'users_page_wpum-custom-fields' ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-fields-editor', 'http://localhost:8080/fields-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_die( 'Vue build missing' );
			}

			wp_enqueue_script( 'wpum-fields-editor' );

			$js_variables = [
				'is_addon_installed'  => apply_filters( 'wpum_fields_editor_has_custom_fields_addon', false ),
				'page_title'          => esc_html__( 'WP User Manager Fields Editor' ),
				'success_message'     => esc_html__( 'Changes successfully saved.' ),
				'labels'              => $this->get_labels(),
				'groups'              => $this->get_groups(),
				'ajax'                => admin_url( 'admin-ajax.php' ),
				'nonce'               => wp_create_nonce( 'wpum_update_fields_groups' ),
			];

			wp_localize_script( 'wpum-fields-editor', 'wpumFieldsEditor', $js_variables );

		}

	}

	/**
	 * Display the fields editor within the admin panel.
	 *
	 * @return void
	 */
	public function display_fields_editor() {
		echo '<div class="wrap"><div id="wpum-fields-editor"></div></div>';
	}

	/**
	 * Define the labels for the interface.
	 *
	 * @return array
	 */
	private function get_labels() {

		return [
			'table_name'                => esc_html__( 'Group name' ),
			'table_desc'                => esc_html__( 'Group description' ),
			'table_default'             => esc_html__( 'Default' ),
			'table_fields'              => esc_html__( 'Fields' ),
			'table_actions'             => esc_html__( 'Actions' ),
			'table_add_group'           => esc_html__( 'Add new field group' ),
			'table_edit_group'          => esc_html__( 'Edit group settings' ),
			'table_edit_fields'         => esc_html__( 'Customize fields' ),
			'table_delete_group'        => esc_html__( 'Delete group' ),
			'table_drag_tooltip'        => esc_html__( 'Drag and drop the rows below to change the order of the fields groups' ),
			'table_default_tooltip'     => esc_html__( 'The default fields group cannot be deleted.' ),
			'modal_group_delete'        => esc_html__( 'You are about to delete the group:' ),
			'modal_delete'              => esc_html__( 'This action cannot be reversed. Are you sure you want to continue? All fields within this group will be deleted too.' ),
			'confirm_delete'            => esc_html__( 'Confirm delete' ),
			'save'                      => esc_html__( 'Save changes' ),
			'tooltip_group_name'        => esc_html__( 'Customize the name of group. This may be used in your theme.' ),
			'tooltip_group_description' => esc_html__( 'Customize the description of the group. This may be used into your theme.' )
		];

	}

	/**
	 * Retrieve a formatted list of fields groups from the database.
	 *
	 * @return array
	 */
	private function get_groups() {

		$groups            = WPUM()->fields_groups->get_groups( [
			'orderby' => 'group_order',
			'order'   => 'ASC'
		] );
		$registered_groups = [];

		if( ! empty( $groups ) && is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				$registered_groups[] = [
					'id'          => $group->get_ID(),
					'name'        => $group->get_name(),
					'description' => $group->get_description(),
					'default'     => $group->get_ID() === 1 ? true: false,
					'fields'      => 0
				];
			}
		}

		return $registered_groups;

	}

	/**
	 * Update groups order within the database.
	 *
	 * @return void
	 */
	public function update_groups_order() {

		check_ajax_referer( 'wpum_update_fields_groups', 'nonce' );

		$groups = isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) && ! empty( $_POST['groups'] ) ? $_POST['groups'] : false;

		if( $groups ) {
			foreach( $groups as $order => $group ) {
				$group_id = (int) $group['id'];
				if( $group_id ) {
					$updated_group = WPUM()->fields_groups->update( $group_id, [ 'group_order' => $order ] );
				}
			}
		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the groups order.' ), 403 );
		}

		wp_send_json_success( $groups );

	}

	/**
	 * Update a fields group via ajax.
	 *
	 * @return void
	 */
	public function update_group() {

		check_ajax_referer( 'wpum_update_fields_groups', 'nonce' );

		$group_id          = isset( $_POST['group_id'] ) && ! empty( $_POST['group_id'] ) ? (int) $_POST['group_id'] : false;
		$group_name        = isset( $_POST['group_name'] ) && ! empty( $_POST['group_name'] ) ? sanitize_text_field( $_POST['group_name'] ) : false;
		$group_description = isset( $_POST['group_description'] ) && ! empty( $_POST['group_description'] ) ? wp_kses_post( $_POST['group_description'] ) : '';

		if( $group_id && $group_name ) {

			$updated_group = WPUM()->fields_groups->update( $group_id, [
				'name'        => $group_name,
				'description' => $group_description
			] );

		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the group details.' ), 403 );
		}

		wp_send_json_success( [
			'id'          => $group_id,
			'name'        => $group_name,
			'description' => $group_description
		] );

	}

}

new WPUM_Fields_Editor;
