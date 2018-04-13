<?php
/**
 * Handles the registration forms editor.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Registration_Forms_Editor {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function init_hooks() {
		add_action( 'admin_menu', [ $this, 'setup_menu_page' ], 9 );
		add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
		add_action( 'wp_ajax_wpum_get_registration_forms', [ $this, 'get_forms' ] );
		add_action( 'wp_ajax_wpum_get_registration_form', [ $this, 'get_form' ] );
	}

	/**
	 * Add the registration forms editor link to the admin dashboard menu.
	 *
	 * @return void
	 */
	public function setup_menu_page() {
		add_users_page(
			esc_html__( 'WP User Manager Registration Forms Editor' ),
			esc_html__( 'Registration forms' ),
			'manage_options',
			'wpum-registration-forms',
			[ $this, 'display_registration_forms_editor' ]
		);
	}

	/**
	 * Registration forms editor page. Display is handled by Vuejs.
	 *
	 * @return void
	 */
	public function display_registration_forms_editor() {
		echo '<div class="wrap"><div id="wpum-registration-forms-editor"></div></div>';
	}

	/**
	 * Load scripts and styles within the new admin page.
	 *
	 * @return void
	 */
	public function load_scripts() {

		$screen = get_current_screen();

		if( $screen->base == 'users_page_wpum-registration-forms' ) {

			$is_vue_dev = defined( 'WPUM_VUE_DEV' ) && WPUM_VUE_DEV ? true : false;

			if( $is_vue_dev ) {
				wp_register_script( 'wpum-registration-forms-editor', 'http://localhost:8080/registration-forms-editor.js', array(), WPUM_VERSION, true );
			} else {
				wp_die( 'Vue build missing' );
			}

			wp_enqueue_script( 'wpum-registration-forms-editor' );
			wp_enqueue_style( 'wpum-registration-forms-editor', WPUM_PLUGIN_URL . 'assets/css/admin/fields-editor.css' , array(), WPUM_VERSION );

			$js_variables = [
				'labels'        => $this->get_labels(),
				'ajax'          => admin_url( 'admin-ajax.php' ),
				'pluginURL'     => WPUM_PLUGIN_URL,
				'getFormsNonce' => wp_create_nonce( 'wpum_get_registration_forms' ),
				'getFormNonce'  => wp_create_nonce( 'wpum_get_registration_form' )
			];

			wp_localize_script( 'wpum-registration-forms-editor', 'wpumRegistrationFormsEditor', $js_variables );

		}

	}

	/**
	 * Setup the labels for translation.
	 *
	 * @return void
	 */
	private function get_labels() {

		$labels = [
			'page_title'             => esc_html__( 'WP User Manager Registration Forms Editor' ),
			'table_name'             => esc_html__( 'Form name' ),
			'table_fields'           => esc_html__( 'Fields' ),
			'table_default'          => esc_html__( 'Default' ),
			'table_role'             => esc_html__( 'Registration role' ),
			'table_not_found'        => esc_html__( 'No registration forms have been found.' ),
			'table_default_tooltip'  => esc_html__( 'The default registration form cannot be deleted.' ),
			'table_customize'        => esc_html__( 'Customize fields' ),
			'page_back'              => esc_html__( 'Return to the registration forms list' ),
			'editor_available_title' => esc_html__( 'Available fields' ),
			'editor_available_desc'  => esc_html__( 'To add a field to this form, drag it into the container on the right. To remove a field, place it back here.' ),
			'table_field_name'       => esc_html__( 'Field name' ),
			'editor_used_fields'     => esc_html__( 'Add fields here to use them in this registration form. Drag fields up and down to change their order within the form.' ),
			'editor_drag'            => esc_html__( 'This form does not have any fields yet. Drag and drop fields here.' ),
		];

		return $labels;

	}

	/**
	 * Retrieve the list of registration forms.
	 *
	 * @return void
	 */
	public function get_forms() {

		check_ajax_referer( 'wpum_get_registration_forms', 'nonce' );

		if( current_user_can( 'manage_options' ) ) {

			$registration_forms = WPUM()->registration_forms->get_forms();
			$forms              = [];

			foreach ( $registration_forms as $form ) {
				$forms[ $form->get_ID() ] = [
					'name'    => $form->get_name(),
					'default' => $form->is_default(),
					'role'    => $form->get_role(),
					'count'   => $form->get_fields_count()
				];
			}

			if( is_array( $forms ) && ! empty( $forms ) ) {
				wp_send_json_success( $forms );
			} else {
				wp_send_json_error( null, 403 );
			}

		} else {
			wp_send_json_error( null, 403 );
		}

	}

	/**
	 * Retrieve a single registration form given an ID.
	 *
	 * @return void
	 */
	public function get_form() {

		check_ajax_referer( 'wpum_get_registration_form', 'nonce' );

		if( current_user_can( 'manage_options' ) ) {

			$form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

			if( $form_id ) {

				$form = WPUM()->registration_forms->get( $form_id );
				$form = new WPUM_Registration_Form( $form->id );

				wp_send_json_success(
					[
						'name'             => $form->get_name(),
						'available_fields' => $this->get_available_fields()
					]
				 );

			} else {

				wp_send_json_error( null, 403 );

			}

		} else {

			wp_send_json_error( null, 403 );

		}

	}

	/**
	 * Get fields available to be used within a registration form.
	 *
	 * @return array
	 */
	private function get_available_fields() {

		$fields = [];

		$available_fields = WPUM()->fields->get_fields( [
			'orderby' => 'fields_order',
			'order'   => 'ASC'
		] );

		$non_allowed_fields = [
			'user_avatar',
			'user_nickname',
			'user_displayname'
		];

		foreach ( $available_fields as $field ) {

			if( ! empty( $field->get_primary_id() ) && in_array( $field->get_primary_id(), $non_allowed_fields ) ) {
				continue;
			}

			$fields[] = [
				'id'   => $field->get_ID(),
				'name' => $field->get_name(),
			];
		}

		return $fields;

	}

}

$wpum_registration_forms_editor = new WPUM_Registration_Forms_Editor;
