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
		add_action( 'wp_ajax_wpum_get_fields_from_group', [ $this, 'get_fields' ] );
		add_action( 'wp_ajax_wpum_update_fields_order', [ $this, 'update_fields_order' ] );
		add_action( 'wp_ajax_wpum_get_field_settings', [ $this, 'get_field_settings' ] );
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
			wp_enqueue_style( 'wpum-fields-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css' , array(), WPUM_VERSION );

			$js_variables = [
				'is_addon_installed' => apply_filters( 'wpum_fields_editor_has_custom_fields_addon', true ),
				'page_title'         => esc_html__( 'WP User Manager Fields Editor' ),
				'success_message'    => esc_html__( 'Changes successfully saved.' ),
				'labels'             => $this->get_labels(),
				'groups'             => $this->get_groups(),
				'ajax'               => admin_url( 'admin-ajax.php' ),
				'pluginURL'          => WPUM_PLUGIN_URL,
				'nonce'              => wp_create_nonce( 'wpum_update_fields_groups' ),
				'get_fields_nonce'   => wp_create_nonce( 'wpum_get_fields' ),
				'cf_addon_url'       => 'https://wpusermanager.com/addons/custom-fields/?ref=wp_admin',
				'fields_types'       => wpum_get_registered_field_types(),
				'edit_dialog_tabs'   => wpum_get_edit_field_dialog_tabs()
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
			'table_drag_tooltip'        => esc_html__( 'Drag and drop the rows below to change the order.' ),
			'table_default_tooltip'     => esc_html__( 'The default fields group cannot be deleted.' ),
			'modal_group_delete'        => esc_html__( 'You are about to delete the group:' ),
			'modal_delete'              => esc_html__( 'This action cannot be reversed. Are you sure you want to continue? All fields within this group will be deleted too.' ),
			'confirm_delete'            => esc_html__( 'Confirm delete?' ),
			'save'                      => esc_html__( 'Save changes' ),
			'tooltip_group_name'        => esc_html__( 'Customize the name of group. This may be used in your theme.' ),
			'tooltip_group_description' => esc_html__( 'Customize the description of the group. This may be used into your theme.' ),
			'purchase'                  => esc_html__( 'Purchase now' ),
			'create_group'              => esc_html__( 'Create new fields group' ),
			'premium_addon'             => sprintf( __( 'Create <a href="%1$s" target="_blank">unlimited custom fields and groups</a> for user profiles and registration forms with a drag & drop interface. The <a href="%1$s" target="_blank">custom fields</a> addon is required if you wish to extend your community.' ), 'https://wpusermanager.com/addons/custom-fields/?ref=wp_admin' ),
			'fields_page_title'         => esc_html__( 'Editing:' ),
			'fields_go_back'            => esc_html__( 'Back to the groups list' ),
			'fields_add_new'            => esc_html__( 'Add new custom field' ),
			'fields_create'             => esc_html__( 'Create custom field' ),
			'fields_name'               => esc_html__( 'Field name' ),
			'fields_type'               => esc_html__( 'Type' ),
			'fields_required'           => esc_html__( 'Required' ),
			'fields_visibility'         => esc_html__( 'Privacy' ),
			'fields_edit'               => esc_html__( 'Edit field' ),
			'fields_delete'             => esc_html__( 'Delete field' ),
			'fields_editable'           => esc_html__( 'Editable' ),
			'fields_default_tooltip'    => esc_html__( 'Default fields cannot be deleted.' ),
			'fields_required_tooltip'   => esc_html__( 'Fields marked as required will be compulsory within the registration and account form.' ),
			'fields_editable_tooltip'   => esc_html__( 'Fields marked as locked, can only be edited by an administrator and will not be visible in any form.' ),
			'fields_visibility_tooltip' => esc_html__( 'Hidden fields are not publicly visible within profiles.' ),
			'fields_not_found'          => esc_html__( 'This fields group is empty.' ),
			'fields_delete_1'           => esc_html__( 'You are about to delete the field:' ),
			'fields_delete_2'           => esc_html__( 'This action cannot be reversed. Are you sure you want to continue? Please note any users data associated with this field will not be removed.' ),
			'field_new_name'            => esc_html__( 'Field name' ),
			'field_new_placeholder'     => esc_html__( 'Enter a name for this field' ),
			'field_edit_general'        => esc_html__( 'General settings' ),
			'field_edit_privacy'        => esc_html__( 'Privacy settings' ),
			'field_edit_customization'  => esc_html__( 'Editing permissions' ),
			'field_edit_settings_error' => esc_html__( 'Something went wrong, could not find the settings for this field type.' ),
			'field_error_required'      => esc_html__( 'Error: this setting is required.' ),
			'field_error_special'       => esc_html__( 'Error: this setting cannot contain special characters.' ),
			'field_error_nosave'        => esc_html__( 'There are some errors with your changes. Please check the errors highlighted below.' )
		];

	}

	/**
	 * Retrieve a formatted list of fields groups from the database.
	 *
	 * @return array
	 */
	private function get_groups() {

		$registered_groups = [];
		$groups            = WPUM()->fields_groups->get_groups( [
			'orderby' => 'group_order',
			'order'   => 'ASC'
		] );

		if( ! empty( $groups ) && is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				$registered_groups[] = [
					'id'          => $group->get_ID(),
					'name'        => $group->get_name(),
					'description' => $group->get_description(),
					'default'     => $group->get_ID() === 1 ? true: false,
					'fields'      => $group->get_count()
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

		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the groups order.' ), 403 );
		}

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

		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the group details.' ), 403 );
		}

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

	/**
	 * Retrieve fields from the database given a group id.
	 *
	 * @return void
	 */
	public function get_fields() {

		check_ajax_referer( 'wpum_get_fields', 'nonce' );

		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Something went wrong while retrieving the list of fields.' ), 403 );
		}

		$fields = [];

		$group_id = isset( $_GET['group_id'] ) && ! empty( $_GET['group_id'] ) ? (int) $_GET['group_id'] : false;

		if( $group_id ) {

			$group_fields = WPUM()->fields->get_fields( [
				'group_id' => 1,
				'orderby' => 'field_order',
				'order'    => 'ASC'
			] );

			foreach( $group_fields as $field ) {

				$fields[] = [
					'id'            => $field->get_ID(),
					'group_id'      => $field->get_group_id(),
					'field_order'   => $field->get_field_order(),
					'type'          => $field->get_type(),
					'type_nicename' => $field->get_type_nicename(),
					'name'          => $field->get_name(),
					'description'   => $field->get_description(),
					'visibility'    => $field->get_visibility(),
					'editable'      => $field->get_editable(),
					'default'       => $field->is_primary(),
					'required'      => $field->is_required()
				];

			}

		} else {
			wp_die( esc_html__( 'Something went wrong while retrieving the list of fields.' ), 403 );
		}

		wp_send_json_success( [
			'fields'   => $fields,
			'group_id' => $group_id
		] );

	}

	/**
	 * Update the order of the fields into the database.
	 *
	 * @return void
	 */
	public function update_fields_order() {

		check_ajax_referer( 'wpum_update_fields_groups', 'nonce' );

		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the fields order.' ), 403 );
		}

		$fields = isset( $_POST['fields'] ) && is_array( $_POST['fields'] ) && ! empty( $_POST['fields'] ) ? $_POST['fields'] : false;

		if( $fields ) {
			foreach ( $fields as $order => $field ) {
				$field_id = (int) $field['id'];
				if( $field_id ) {
					$updated_field = WPUM()->fields->update( $field_id, [ 'field_order' => $order ] );
				}
			}
		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the fields order.' ), 403 );
		}

		wp_send_json_success( $fields );

	}

	/**
	 * Retrieve the list of settings for a given field type.
	 *
	 * This will generate:
	 * - Form schema for vuejs within the 'settings' key.
	 * - Model data for vuejs within the 'model' key.
	 *
	 * @return void
	 */
	public function get_field_settings() {

		check_ajax_referer( 'wpum_get_fields', 'nonce' );

		if( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( null, 403 );
		}

		$field_type        = $this->field_type_exists( $_POST['field_type'] );
		$fields_type_group = sanitize_text_field( $_POST['group'] );
		$wpum_field_id     = absint( sanitize_text_field( $_POST['field_id'] ) );

		if( is_array( $field_type ) && ! empty( $field_type ) && $wpum_field_id ) {

			// Let's grab the settings for this field.
			$settings = $field_type[0]['settings'];
			$settings = $settings[ $fields_type_group ];
			$model    = [];

			// Generate the model array for vuejs.
			foreach( $settings as $setting ) {
				$model[ $setting['model'] ] = $this->get_setting_value( $wpum_field_id, $setting['model'] );
			}

			wp_send_json_success( [
				'settings' => $settings,
				'model'    => ( object ) $model
			] );

		} else {
			wp_send_json_error( null, 403 );
		}

	}

	/**
	 * Determine if a given field type is a registered field type.
	 *
	 * @param string $type
	 * @return mixed
	 */
	private function field_type_exists( $type = null ) {

		$exists = false;

		if( $type ) {

			// Grab all registered field types.
			$registered_fields = wpum_get_registered_field_types();
			$criteria          = array( 'type' => $type );
			$default_fields    = wp_list_filter( $registered_fields['default']['fields'], $criteria );
			$standard_fields   = wp_list_filter( $registered_fields['standard']['fields'], $criteria );
			$advanced_fields   = wp_list_filter( $registered_fields['advanced']['fields'], $criteria );

			// If it's found in either the default, standard or advanced fields groups then it's a success!
			if( is_array( $default_fields ) && ! empty( $default_fields ) ) {
				$exists = $default_fields;
			} else if( is_array( $standard_fields ) && ! empty( $standard_fields ) ) {
				$exists = $standard_fields;
			} else if( is_array( $advanced_fields ) && ! empty( $advanced_fields ) ) {
				$exists = $advanced_fields;
			}

		}

		return $exists;

	}

	/**
	 * Retrieve the value of field setting given the setting id.
	 *
	 * @param string $wpum_field_id
	 * @param string $setting_id
	 * @return void
	 */
	private function get_setting_value( $wpum_field_id, $setting_id ) {

		$value = '';

		if( $wpum_field_id && $setting_id ) {

			$field = new WPUM_Field( $wpum_field_id );

			if( $setting_id == 'field_title' ) {
				$value = $field->get_name();
			} else if( $setting_id == 'field_description' ) {
				$value = $field->get_description();
			} else {
				$value = $field->get_meta( $setting_id );
			}

		}

		return $value;

	}

}

new WPUM_Fields_Editor;
