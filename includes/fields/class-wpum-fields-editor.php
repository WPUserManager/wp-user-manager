<?php
/**
 * Handles all the custom fields related functionalities in the backend.
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
 * The class that handles the fields editor.
 */
class WPUM_Fields_Editor {

	/**
	 * Holds the disabled settings for each field.
	 *
	 * @var array
	 */
	public $deregistered_settings = array();

	/**
	 * @var mixed|void
	 */
	protected $capability;

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->capability = apply_filters( 'wpum_admin_pages_capability', 'manage_options' );
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'setup_menu_page' ), 9 );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_scripts' ) );
		add_action( 'wp_ajax_wpum_update_fields_groups_order', array( $this, 'update_groups_order' ) );
		add_action( 'wp_ajax_wpum_update_fields_group', array( $this, 'update_group' ) );
		add_action( 'wp_ajax_wpum_get_fields_from_group', array( $this, 'get_fields' ) );
		add_action( 'wp_ajax_wpum_update_fields_order', array( $this, 'update_fields_order' ) );
		add_action( 'wp_ajax_wpum_get_field_settings', array( $this, 'get_field_settings' ) );
		add_action( 'wp_ajax_wpum_update_field', array( $this, 'update_field' ) );
		// Object Caching hooks
		add_action( 'wpum_field_group_insert', array( $this, 'trigger_delete_groups_cache' ) );
		add_action( 'wpum_field_group_delete', array( $this, 'trigger_delete_groups_cache' ) );
		add_action( 'wpum_field_group_delete', array( $this, 'trigger_delete_groups_cache_by_id' ) );
		add_action( 'wpum_field_insert', array( $this, 'trigger_delete_group_fields_cache' ), 10, 2 );
		add_action( 'wpum_before_field_delete', array( $this, 'trigger_delete_group_fields_cache_by_id' ) );
	}

	/**
	 * Add new menu page to the "Users" menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'Fields Editor', 'wp-user-manager' ),
			esc_html__( 'Custom Fields', 'wp-user-manager' ),
			$this->capability,
			'wpum-custom-fields',
			array( $this, 'display_fields_editor' )
		);
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if ( 'users_page_wpum-custom-fields' === $screen->base ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV;

			if ( $is_vue_dev ) {
				$vue_dev_port = defined( 'WPUM_VUE_DEV_PORT' ) ? WPUM_VUE_DEV_PORT : '8080';
				wp_register_script( 'wpum-fields-editor', 'http://localhost:' . $vue_dev_port . '/fields-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_register_script( 'wpum-fields-editor', WPUM_PLUGIN_URL . 'dist/static/js/fields-editor.js', array(), WPUM_VERSION, true );
			}

			if ( ! $is_vue_dev ) {
				wp_enqueue_script( 'wpum-vue-manifest' );
				wp_enqueue_script( 'wpum-vue-vendor' );
				wp_enqueue_style( 'wpum-fields-editor-css', WPUM_PLUGIN_URL . 'dist/static/css/fields-editor.css', array(), WPUM_VERSION );
			}

			wp_enqueue_script( 'wpum-fields-editor' );
			wp_enqueue_style( 'wpum-fields-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css', array(), WPUM_VERSION );

			$js_variables = array(
				'is_addon_installed'        => apply_filters( 'wpum_fields_editor_has_custom_fields_addon', false ),
				'page_title'                => esc_html__( 'Fields Editor', 'wp-user-manager' ),
				'success_message'           => esc_html__( 'Changes successfully saved.', 'wp-user-manager' ),
				'labels'                    => $this->get_labels(),
				'groups'                    => $this->get_groups(),
				'ajax'                      => admin_url( 'admin-ajax.php' ),
				'pluginURL'                 => WPUM_PLUGIN_URL,
				'nonce'                     => wp_create_nonce( 'wpum_update_fields_groups' ),
				'delete_fields_group_nonce' => wp_create_nonce( 'wpum_delete_fields_groups' ),
				'get_fields_nonce'          => wp_create_nonce( 'wpum_get_fields' ),
				'create_field_nonce'        => wp_create_nonce( 'wpum_create_field' ),
				'delete_field_nonce'        => wp_create_nonce( 'wpum_delete_field' ),
				'cf_addon_url'              => 'https://wpusermanager.com/addons/custom-fields?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=custom-fields-editor',
				'fields_types'              => wpum_get_registered_field_types(),
				'edit_dialog_tabs'          => wpum_get_edit_field_dialog_tabs(),
			);

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

		return array(
			'table_name'                => esc_html__( 'Group name', 'wp-user-manager' ),
			'table_desc'                => esc_html__( 'Group description', 'wp-user-manager' ),
			'table_default'             => esc_html__( 'Default', 'wp-user-manager' ),
			'table_fields'              => esc_html__( 'Fields', 'wp-user-manager' ),
			'table_actions'             => esc_html__( 'Actions', 'wp-user-manager' ),
			'table_add_group'           => esc_html__( 'Add New Field Group', 'wp-user-manager' ),
			'table_edit_group'          => esc_html__( 'Edit group settings', 'wp-user-manager' ),
			'table_edit_fields'         => esc_html__( 'Customize fields', 'wp-user-manager' ),
			'table_delete_group'        => esc_html__( 'Delete group', 'wp-user-manager' ),
			'table_drag_tooltip'        => esc_html__( 'Drag and drop the rows below to change the order.', 'wp-user-manager' ),
			'table_default_tooltip'     => esc_html__( 'The default fields group cannot be deleted.', 'wp-user-manager' ),
			'modal_group_delete'        => esc_html__( 'You are about to delete the group:', 'wp-user-manager' ),
			'modal_delete'              => esc_html__( 'This action cannot be reversed. Are you sure you want to continue? All fields within this group will be deleted too.', 'wp-user-manager' ),
			'confirm_delete'            => esc_html__( 'Confirm delete?', 'wp-user-manager' ),
			'save'                      => esc_html__( 'Save changes', 'wp-user-manager' ),
			'tooltip_group_name'        => esc_html__( 'Customize the name of group. This may be used in your theme.', 'wp-user-manager' ),
			'tooltip_group_description' => esc_html__( 'Customize the description of the group. This may be used into your theme.', 'wp-user-manager' ),
			'purchase'                  => esc_html__( 'Purchase', 'wp-user-manager' ),
			'create_group'              => esc_html__( 'Create Fields Group', 'wp-user-manager' ),
			// translators: %1$s custom fields addon URL
			'premium_addon'             => sprintf( __( 'Create <a href="%1$s" target="_blank">unlimited custom fields and groups</a> for user profiles and registration forms with a drag & drop interface. The <a href="%1$s" target="_blank">custom fields</a> addon is required if you wish to extend your community.', 'wp-user-manager' ), 'https://wpusermanager.com/addons/custom-fields?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=custom-fields-editor' ),
			'fields_page_title'         => esc_html__( 'Editing:', 'wp-user-manager' ),
			'fields_go_back'            => esc_html__( 'Back to the groups list', 'wp-user-manager' ),
			'fields_add_new'            => esc_html__( 'Add new custom field', 'wp-user-manager' ),
			'fields_create'             => esc_html__( 'Create custom field', 'wp-user-manager' ),
			'fields_name'               => esc_html__( 'Field name', 'wp-user-manager' ),
			'fields_type'               => esc_html__( 'Type', 'wp-user-manager' ),
			'fields_required'           => esc_html__( 'Required', 'wp-user-manager' ),
			'fields_visibility'         => esc_html__( 'Privacy', 'wp-user-manager' ),
			'fields_edit'               => esc_html__( 'Edit field', 'wp-user-manager' ),
			'fields_delete'             => esc_html__( 'Delete field', 'wp-user-manager' ),
			'fields_editable'           => esc_html__( 'Editable', 'wp-user-manager' ),
			'fields_default_tooltip'    => esc_html__( 'Default fields cannot be deleted.', 'wp-user-manager' ),
			'fields_required_tooltip'   => esc_html__( 'Fields marked as required will be compulsory within the registration and account form.', 'wp-user-manager' ),
			'fields_editable_tooltip'   => esc_html__( 'Fields marked as locked, can only be edited by an administrator and will not be visible in any form.', 'wp-user-manager' ),
			'fields_visibility_tooltip' => esc_html__( 'Hidden fields are not publicly visible within profiles.', 'wp-user-manager' ),
			'fields_not_found'          => esc_html__( 'This fields group is empty.', 'wp-user-manager' ),
			'fields_delete_1'           => esc_html__( 'You are about to delete the field:', 'wp-user-manager' ),
			'fields_delete_2'           => esc_html__( 'This action cannot be reversed. Are you sure you want to continue? Please note any users data associated with this field will not be removed.', 'wp-user-manager' ),
			'field_new_name'            => esc_html__( 'Field name', 'wp-user-manager' ),
			'field_new_placeholder'     => esc_html__( 'Enter a name for this field', 'wp-user-manager' ),
			'field_edit_general'        => esc_html__( 'General settings', 'wp-user-manager' ),
			'field_edit_privacy'        => esc_html__( 'Privacy settings', 'wp-user-manager' ),
			'field_edit_customization'  => esc_html__( 'Editing permissions', 'wp-user-manager' ),
			'field_edit_settings_error' => esc_html__( 'Something went wrong, could not find the settings for this field type.', 'wp-user-manager' ),
			'field_error_required'      => esc_html__( 'Error: this setting is required.', 'wp-user-manager' ),
			'field_error_special'       => esc_html__( 'Error: this setting cannot contain special characters.', 'wp-user-manager' ),
			'field_error_nosave'        => esc_html__( 'There are some errors with your changes. Please check the errors highlighted below.', 'wp-user-manager' ),
			'error_general'             => esc_html__( 'Something went wrong, no changes were saved.', 'wp-user-manager' ),
			'registration_info'         => esc_html__( 'To display this field during signup, select one or more registration forms below.', 'wp-user-manager' ),
			'registration_label'        => esc_html__( 'Available registration forms', 'wp-user-manager' ),
			'field_options'             => esc_html__( 'Field options', 'wp-user-manager' ),
			// translators: %s docs URL for pasting custom field options
			'field_options_hint'        => wp_kses_post( sprintf( __( 'Add a value and label for each option. Comma separated lists of values,labels can be pasted. <a href="%s" target="_blank">Learn more</a>', 'wp-user-manager' ), 'https://wpusermanager.com/article/310-populating-a-fields-options-with-a-large-set-of-options/' ) ),
			'field_add_option'          => esc_html__( 'Add option', 'wp-user-manager' ),
			'field_option_label'        => esc_html__( 'Option label', 'wp-user-manager' ),
			'field_option_value'        => esc_html__( 'Option value', 'wp-user-manager' ),
			'repeater_fields_add_new'   => esc_html__( 'Add new sub field', 'wp-user-manager' ),
			'repeater_fields_create'    => esc_html__( 'Add sub field', 'wp-user-manager' ),
			'confirm_message'           => esc_html__( 'Are you sure you want to leave? You have unsaved field settings.', 'wp-user-manager' ),
		);

	}

	/**
	 * Retrieve a formatted list of fields groups from the database.
	 *
	 * @return array
	 */
	private function get_groups() {

		$registered_groups = array();
		$groups            = WPUM()->fields_groups->get_groups(
			array(
				'orderby' => 'group_order',
				'order'   => 'ASC',
			)
		);

		if ( ! empty( $groups ) && is_array( $groups ) ) {
			foreach ( $groups as $group ) {
				$registered_groups[] = array(
					'id'          => $group->get_ID(),
					'name'        => $group->get_name(),
					'description' => $group->get_description(),
					'default'     => $group->get_ID() === 1,
					'fields'      => $group->get_count(),
				);
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

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the groups order.', 'wp-user-manager' ), 403 );
		}

		$groups = filter_input( INPUT_POST, 'groups', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( $groups ) {
			foreach ( $groups as $order => $group ) {
				$group_id = (int) $group['id'];
				if ( $group_id ) {
					$updated_group = WPUM()->fields_groups->update( $group_id, array( 'group_order' => $order ) );
				}
			}
		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the groups order.', 'wp-user-manager' ), 403 );
		}
		$this->delete_groups_cache();
		wp_send_json_success( $groups );

	}

	/**
	 * Update a fields group via ajax.
	 *
	 * @return void
	 */
	public function update_group() {

		check_ajax_referer( 'wpum_update_fields_groups', 'nonce' );

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the group details.', 'wp-user-manager' ), 403 );
		}

		$group_id          = isset( $_POST['group_id'] ) && ! empty( $_POST['group_id'] ) ? filter_input( INPUT_POST, 'group_id', FILTER_VALIDATE_INT ) : false;
		$group_name        = isset( $_POST['group_name'] ) && ! empty( $_POST['group_name'] ) ? sanitize_text_field( wp_unslash( $_POST['group_name'] ) ) : false;
		$group_description = isset( $_POST['group_description'] ) && ! empty( $_POST['group_description'] ) ? wp_kses_post( wp_unslash( $_POST['group_description'] ) ) : '';

		if ( $group_id && $group_name ) {

			$updated_group = WPUM()->fields_groups->update(
				$group_id, apply_filters('wpum_field_group_update', array(
					'name'        => $group_name,
					'description' => $group_description,
				), $group_id )
			);

		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the group details.', 'wp-user-manager' ), 403 );
		}

		$this->delete_groups_cache();

		wp_send_json_success(
			array(
				'id'          => $group_id,
				'name'        => $group_name,
				'description' => $group_description,
			)
		);

	}

	/**
	 * Delete groups cache
	 */
	protected function delete_groups_cache() {
		$args = array(
			'orderby' => 'group_order',
			'order'   => 'ASC',
		);

		$cache_key = WPUM()->fields_groups->get_cache_key_from_args( $args );

		wp_cache_delete( $cache_key, WPUM()->fields_groups->cache_group );
	}

	/**
	 * @param int $group_id
	 * @param int $parent
	 */
	protected function delete_group_fields_cache( $group_id, $parent = 0 ) {
		$args = array(
			'group_id' => (int) $group_id,
			'orderby'  => 'field_order',
			'order'    => 'ASC',
			'parent'   => $parent,
		);

		$cache_key = WPUM()->fields->get_cache_key_from_args( $args );

		wp_cache_delete( $cache_key, WPUM()->fields->cache_group );
	}

	/**
	 * Retrieve fields from the database given a group id.
	 *
	 * @return void
	 */
	public function get_fields() {

		check_ajax_referer( 'wpum_get_fields', 'nonce' );

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Something went wrong while retrieving the list of fields.', 'wp-user-manager' ), 403 );
		}

		$fields = array();

		$group_id = isset( $_GET['group_id'] ) && ! empty( $_GET['group_id'] ) ? (int) $_GET['group_id'] : false;
		$parent   = isset( $_GET['parent_id'] ) ? intval( $_GET['parent_id'] ) : 0;

		if ( $group_id ) {

			$group_fields = WPUM()->fields->get_fields(
				array(
					'group_id' => $group_id,
					'orderby'  => 'field_order',
					'order'    => 'ASC',
					'parent'   => $parent,
				)
			);

			foreach ( $group_fields as $field ) {

				$fields[] = array(
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
					'default_id'    => $field->get_primary_id(),
					'required'      => $field->is_required(),
					'parent_id'     => $field->get_parent_ID(),
				);

			}
		} else {
			wp_die( esc_html__( 'Something went wrong while retrieving the list of fields.', 'wp-user-manager' ), 403 );
		}

		wp_send_json_success(
			array(
				'fields'   => $fields,
				'group_id' => $group_id,
			)
		);

	}

	/**
	 * Update the order of the fields into the database.
	 *
	 * @return void
	 */
	public function update_fields_order() {

		check_ajax_referer( 'wpum_update_fields_groups', 'nonce' );

		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'Something went wrong: could not update the fields order.', 'wp-user-manager' ), 403 );
		}

		$fields = filter_input( INPUT_POST, 'fields', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		$group_id = false;
		if ( $fields ) {
			foreach ( $fields as $order => $field ) {
				$group_id = $field['group_id'];
				$field_id = (int) $field['id'];
				if ( $field_id ) {
					$updated_field = WPUM()->fields->update( $field_id, array( 'field_order' => $order ) );
				}
			}
		} else {
			wp_die( esc_html__( 'Something went wrong: could not update the fields order.', 'wp-user-manager' ), 403 );
		}

		$this->delete_group_fields_cache( $group_id );
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

		if ( ! current_user_can( $this->capability ) ) {
			wp_send_json_error( null, 403 );
		}

		$field_type        = filter_input( INPUT_POST, 'field_type' );
		$field_type        = $this->field_type_exists( $field_type );
		$fields_type_group = filter_input( INPUT_POST, 'group' );
		$wpum_field_id     = filter_input( INPUT_POST, 'field_id', FILTER_VALIDATE_INT );

		if ( is_array( $field_type ) && ! empty( $field_type ) && $wpum_field_id ) {

			// Let's grab the settings for this field.
			reset( $field_type );
			$first_key = key( $field_type );

			$settings = $field_type[ $first_key ]['settings'];
			$settings = apply_filters( 'wpum_fields_editor_field_settings', $settings[ $fields_type_group ], $field_type[ $first_key ], $fields_type_group );
			$model    = array();

			// Generate the model array for vuejs.
			foreach ( $settings as $setting ) {
				$model[ $setting['model'] ] = $this->get_setting_value( $wpum_field_id, $setting['model'], $setting['type'] );
			}

			// Deregister some settings from the editor.
			$wpum_field       = new WPUM_Field( $wpum_field_id );
			$settings         = $this->deregister_settings( $settings, $wpum_field );
			$model            = $this->deregister_model( $model, $wpum_field->get_primary_id() );
			$dropdown_options = $wpum_field->get_meta( 'dropdown_options' );

			// Now send data to vuejs.
			wp_send_json_success(
				array(
					'settings'        => $settings,
					'model'           => (object) $model,
					'dropdownOptions' => ! empty( $dropdown_options ) && is_array( $dropdown_options ) ? (array) $dropdown_options : array(),
				)
			);

		} else {
			wp_send_json_error( null, 403 );
		}

	}

	/**
	 * Deregister settings for fields that do not require all of them.
	 *
	 * @param array      $settings
	 * @param WPUM_Field $wpum_field
	 *
	 * @return array
	 */
	private function deregister_settings( $settings, $wpum_field ) {

		$this->deregistered_settings = array();

		if ( ! empty( $wpum_field->get_primary_id() ) ) {
			// All primary fields do not need the meta key setting.
			$this->deregistered_settings[] = 'user_meta_key';

			switch ( $wpum_field->get_primary_id() ) {
				case 'username':
				case 'user_displayname':
				case 'user_avatar':
				case 'user_cover':
					$this->deregistered_settings[] = 'placeholder';
					break;
			}
		}

		if ( is_array( $this->deregistered_settings ) && ! empty( $this->deregistered_settings ) ) {
			foreach ( $this->deregistered_settings as $setting_key ) {
				unset( $settings[ $setting_key ] );
			}
		}

		return apply_filters( 'wpum_fields_editor_deregister_settings', $settings, $wpum_field->get_primary_id(), $wpum_field );

	}

	/**
	 * Deregister models for fields that are no longer required.
	 *
	 * @param array $model
	 * @param mixed $primary_field_id
	 *
	 * @return array
	 */
	private function deregister_model( $model, $primary_field_id ) {

		if ( is_array( $this->deregistered_settings ) && ! empty( $this->deregistered_settings ) ) {
			foreach ( $this->deregistered_settings as $model_key ) {
				unset( $model[ $model_key ] );
			}
		}

		return apply_filters( 'wpum_fields_editor_deregister_model', $model, $primary_field_id );

	}

	/**
	 * Determine if a given field type is a registered field type.
	 *
	 * @param string $type
	 * @return mixed
	 */
	private function field_type_exists( $type = null ) {

		$exists = false;

		if ( $type ) {

			// Grab all registered field types.
			$registered_fields = wpum_get_registered_field_types();
			$criteria          = array( 'type' => $type );
			$default_fields    = wp_list_filter( $registered_fields['default']['fields'], $criteria );
			$standard_fields   = wp_list_filter( $registered_fields['standard']['fields'], $criteria );
			$advanced_fields   = wp_list_filter( $registered_fields['advanced']['fields'], $criteria );

			// If it's found in either the default, standard or advanced fields groups then it's a success!
			if ( is_array( $default_fields ) && ! empty( $default_fields ) ) {
				$exists = $default_fields;
			} elseif ( is_array( $standard_fields ) && ! empty( $standard_fields ) ) {
				$exists = $standard_fields;
			} elseif ( is_array( $advanced_fields ) && ! empty( $advanced_fields ) ) {
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
	 * @param string $type
	 *
	 * @return string
	 */
	private function get_setting_value( $wpum_field_id, $setting_id, $type ) {

		$value = '';

		if ( $wpum_field_id && $setting_id ) {

			$field = new WPUM_Field( $wpum_field_id );

			if ( 'field_title' === $setting_id ) {
				$value = $field->get_name();
			} elseif ( 'field_description' === $setting_id ) {
				$value = $field->get_description();
			} elseif ( 'user_meta_key' === $setting_id ) {

				if ( 'file' === $field->get_type() && strpos( $field->get_meta( $setting_id ), 'wpum_file_field_' ) === 0 ) {

					$value  = $field->get_meta( $setting_id );
					$prefix = 'wpum_file_field_';
					$str    = $value;

					if ( substr( $str, 0, strlen( $prefix ) ) === $prefix ) {
						$str = substr( $str, strlen( $prefix ) );
					}

					$value = $str;
				} elseif ( $field->get_type() !== 'file' && strpos( $field->get_meta( $setting_id ), 'wpum_' ) === 0 ) {
					$value  = $field->get_meta( $setting_id );
					$prefix = 'wpum_';
					$str    = $value;
					if ( substr( $str, 0, strlen( $prefix ) ) === $prefix ) {
						$str = substr( $str, strlen( $prefix ) );
					}
					$value = $str;
				} else {
					$value = $field->get_meta( $setting_id );
				}
			} else {
				if ( 'checkbox' === $type ) {
					$value = (bool) $field->get_meta( $setting_id );
				} else {
					$default_method = 'default_' . $setting_id;
					if ( method_exists( $field->field_type, $default_method ) ) {
						if ( ! $field->meta_exists( $setting_id ) ) {
							return $field->field_type->{$default_method}();
						}
					}

					$value = $field->get_meta( $setting_id );
				}
			}
		}

		return $value;
	}

	/**
	 * Delete groups cache
	 */
	public function trigger_delete_groups_cache() {
		$this->delete_groups_cache();
	}

	/**
	 * @param int $group_id
	 */
	public function trigger_delete_groups_cache_by_id( $group_id ) {
		$this->delete_groups_cache();
		$this->delete_group_fields_cache( $group_id );
	}

	/**
	 * @param array $data
	 * @param int   $field_id
	 */
	public function trigger_delete_group_fields_cache( $data, $field_id ) {
		$field = new WPUM_Field( $field_id );
		$this->delete_group_fields_cache( $field->get_group_id() );
	}

	/**
	 * @param int $field_id
	 */
	public function trigger_delete_group_fields_cache_by_id( $field_id ) {
		$field = new WPUM_Field( $field_id );
		$this->delete_group_fields_cache( $field->get_group_id() );
	}

	/**
	 * Update a field within the database.
	 * Verify the field exists first, sanitize the given data and then update the db.
	 *
	 * @return void
	 */
	public function update_field() {

		check_ajax_referer( 'wpum_get_fields', 'nonce' );

		if ( ! current_user_can( $this->capability ) ) {
			wp_send_json_error( null, 403 );
		}

		$field_id         = filter_input( INPUT_POST, 'field_id' );
		$data             = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$setting_fields   = filter_input( INPUT_POST, 'settings', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$dropdown_options = filter_input( INPUT_POST, 'dropdownOptions', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		$field_to_update  = new WPUM_Field( $field_id );

		if ( $field_to_update->exists() ) {
			foreach ( $data as $setting_id => $setting_data ) {
				if ( in_array( $setting_id, apply_filters( 'wpum_update_field_excluded_settings', array( 'conditions' ) ), true ) ) {
					continue;
				}

				// Update the name and description.
				if ( 'field_title' === $setting_id ) {
					$field_to_update->update( array( 'name' => sanitize_text_field( $setting_data ) ) );
				} elseif ( 'field_description' === $setting_id ) {
					$field_to_update->update( array( 'description' => wp_kses_post( $setting_data ) ) );
					// Now update the meta data.
				} elseif ( 'user_meta_key' === $setting_id && ! $field_to_update->is_primary() ) {
					if ( strpos( $setting_data, 'wpum_' ) !== 0 ) {
						$setting_data = 'wpum_' . $setting_data;
						$setting_data = $field_to_update->wpum_sanitize_key( $setting_data );
						if ( 'file' === $field_to_update->get_type() ) {
							$append_key   = str_replace( 'wpum_file_field_', '', $setting_data );
							$append_key   = str_replace( 'wpum_', '', $append_key );
							$setting_data = 'wpum_file_field_' . $append_key;
						}
					}
					$field_to_update->update_meta( $setting_id, $setting_data );
				} else {

					// Find the type of input for this setting.
					$criteria       = array( 'model' => $setting_id );
					$setting_config = wp_list_filter( $setting_fields, $criteria );

					// Find the first key of the array within the setting config array.
					reset( $setting_config );
					$first_key      = key( $setting_config );
					$setting_config = isset( $setting_config[ $first_key ] ) ? $setting_config[ $first_key ] : false;

					if ( is_array( $setting_config ) && array_key_exists( 'type', $setting_config ) ) {

						$setting_type = $setting_config['type'];

						switch ( $setting_type ) {
							case 'input':
							case 'radios':
								$setting_data = sanitize_text_field( $setting_data );
								break;
							case 'textarea':
								$setting_data = wp_kses_post( $setting_data );
								break;
							case 'checkbox':
								$setting_data = 'true' === $setting_data;
								break;
							case 'multiselect':
								$setting_data = $setting_data;
								break;
							default:
								$sanitize_method = apply_filters( 'wpum_update_fields_sanitize_method', 'sanitize_text_field', $setting_type, $setting_data );
								$setting_data    = call_user_func( $sanitize_method, $setting_data );
								break;
						}

						// Now finally save the data.
						if ( $setting_data ) {
							$field_to_update->update_meta( $setting_id, $setting_data );
						} else {
							$field_to_update->delete_meta( $setting_id );
						}
					}
				}
			}

			if ( is_array( $dropdown_options ) && ! empty( $dropdown_options ) ) {
				$options = array();
				foreach ( $dropdown_options as $key => $value ) {
					$options[ $key ] = array(
						'value' => sanitize_text_field( $value['value'] ),
						'label' => sanitize_text_field( $value['label'] ),
					);
				}
				$field_to_update->update_meta( 'dropdown_options', $options );
			}

			$this->delete_group_fields_cache( $field_to_update->get_group_id() );
			wp_send_json_success( $data );

		} else {
			wp_send_json_error( null, 403 );
		}

	}

}

new WPUM_Fields_Editor();
