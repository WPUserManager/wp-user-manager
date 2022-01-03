<?php
/**
 * Handles the WPUM account page.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPUM_Form_Profile extends WPUM_Form {

	use WPUM_Form_Account;

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'profile';

	/**
	 * Determine if there's a referrer.
	 *
	 * @var mixed
	 */
	protected $referrer;

	/**
	 * Stores static instance of class.
	 *
	 * @access protected
	 * @var WPUM_Form_Login The single instance of the class
	 */
	protected static $_instance = null;

	/**
	 * Holds the currently logged in user.
	 *
	 * @var integer
	 */
	protected $user = null;

	/**
	 * Returns static instance of class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		if ( ! is_user_logged_in() ) {
			return;
		}

		$this->user = wp_get_current_user();

		add_filter( 'submit_wpum_form_validate_fields', [ $this, 'validate_nickname' ], 10, 4 );

		add_action( 'wp', array( $this, 'process' ) );

		$this->steps = (array) apply_filters(
			'wpum_account_tabs',
			array(
				'account' => array(
					'name'     => esc_html__( 'Profile settings', 'wp-user-manager' ),
					'view'     => array( $this, 'show_form' ),
					'handler'  => array( $this, 'account_handler' ),
					'priority' => 10,
				),
			)
		);

		uasort( $this->steps, array( $this, 'sort_by_priority' ) );

		if ( isset( $_POST['step'] ) ) {
			$this->step = is_numeric( $_POST['step'] ) ? max( absint( $_POST['step'] ), 0 ) : array_search( $_POST['step'], array_keys( $this->steps ) );
		} elseif ( ! empty( $_GET['step'] ) ) {
			$this->step = is_numeric( $_GET['step'] ) ? max( absint( $_GET['step'] ), 0 ) : array_search( $_GET['step'], array_keys( $this->steps ) );
		}

	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$this->fields = apply_filters(
			'account_page_form_fields',
			array(
				'account' => $this->get_account_fields(),
			)
		);

	}

	/**
	 * Make sure the nickname and display name options are unique.
	 *
	 * @param boolean $pass
	 * @param array   $fields
	 * @param array   $values
	 * @param string  $form
	 * @return mixed
	 */
	public function validate_nickname( $pass, $fields, $values, $form ) {

		if ( $form == $this->form_name && isset( $values['account']['user_nickname'] ) ) {

			global $wpdb;

			$displayname = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE display_name = %s AND ID <> %d", $values['account']['user_displayname'], $this->user->ID ) );
			$nickname    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname' AND meta.meta_value = %s AND users.ID <> %d", $values['account']['user_nickname'], $this->user->ID ) );

			if ( $displayname == '1' ) {
				return new WP_Error( 'displayname-unique-validation-error', esc_html__( 'This display name is already in use by someone else. Display names must be unique.', 'wp-user-manager' ) );
			}

			if ( $nickname == '1' ) {
				return new WP_Error( 'displayname-unique-validation-error', esc_html__( 'This nickname is already in use by someone else. Nicknames must be unique.', 'wp-user-manager' ) );
			}
		}

		return $pass;

	}

	/**
	 * Retrieve the list of fields for the account page.
	 *
	 * @return array
	 */
	private function get_account_fields() {

		$fields = [];

		$account_fields = WPUM()->fields->get_fields(
			[
				'group_id' => 1,
				'orderby'  => 'field_order',
				'order'    => 'ASC',
			]
		);

		$priority = 0;

		foreach ( $account_fields as $field ) {

			$priority ++;

			$field = new WPUM_Field( $field );

			if ( $field->exists() && $field->get_primary_id() !== 'user_password' ) {

				if ( ! apply_filters( 'wpum_account_display_field', $field->get_meta( 'editing' ) === 'public', $field ) ) {
					continue;
				}

				// Skip the avatar field if disabled.
				if ( $field->get_primary_id() == 'user_avatar' && ! wpum_get_option( 'custom_avatars' ) ) {
					continue;
				}

				$data = array(
					'id'            => $field->get_id(),
					'label'         => $field->get_name(),
					'type'          => $field->get_type(),
					'required'      => $field->get_meta( 'required' ),
					'placeholder'   => $field->get_meta( 'placeholder' ),
					'description'   => $field->get_description(),
					'read_only'     => $field->get_meta( 'read_only' ),
					'max_file_size' => $field->get_meta( 'max_file_size' ),
					'options'       => $this->get_field_dropdown_options( $field, $this->user ),
					'value'         => $this->get_user_field_value( $this->user, $field ),
					'priority'      => $priority,
					'template'      => $field->get_parent_type(),
					'roles'         => $field->get_meta( 'roles' )
				);

				$data = array_merge( $data, $field->get_field_data() );

				$fields[ $this->get_parsed_id( $field->get_name(), $field->get_primary_id(), $field ) ] = $data;
			}
		}

		$fields = apply_filters( 'wpum_get_account_fields', $fields );

		if ( ! wpum_get_option( 'custom_avatars' ) && isset( $fields['user_avatar'] ) ) {
			unset( $fields['user_avatar'] );
		}

		if ( wpum_get_option( 'disable_profile_cover' ) && isset( $fields['user_cover'] ) ) {
			unset( $fields['user_cover'] );
		}

		return $fields;

	}

	/**
	 * Display the account form.
	 *
	 * @return void
	 */
	public function show_form() {

		$this->init_fields();

		$data = [
			'form'      => $this->form_name,
			'action'    => $this->get_action(),
			'fields'    => $this->get_fields( 'account' ),
			'step'      => $this->get_step(),
			'step_name' => $this->steps[ $this->get_step_key( $this->get_step() ) ]['name'],
		];

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'account' );

	}

	/**
	 * Update the user profile.
	 *
	 * @return void
	 */
	public function account_handler() {

		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			if ( ! wp_verify_nonce( $_POST['account_update_nonce'], 'verify_account_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_account'] ) ) {
				return;
			}

			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			do_action( 'wpum_before_user_update', $this, $values, $this->user->ID );

			$updated_user_id = $this->update_account_values( $this->user, $values );

			do_action( 'wpum_after_user_update', $this, $values, $updated_user_id );

			// Successful, the success message now.
			$redirect = get_permalink();
			$redirect = add_query_arg(
				[
					'updated' => 'success',
				],
				$redirect
			);

			wp_safe_redirect( $redirect );
			exit;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'account_handler' );
			return;
		}

	}

	/**
	 * Prepare the correct value for the display name option.
	 *
	 * @param array  $values
	 * @param string $value
	 * @return string
	 */
	private function parse_displayname( $values, $value ) {

		$name = $this->user->user_login;

		switch ( $value ) {
			case 'display_nickname':
				$name = $values['user_nickname'];
				break;
			case 'display_firstname':
				$name = $values['user_firstname'];
				break;
			case 'display_lastname':
				$name = $values['user_lastname'];
				break;
			case 'display_firstlast':
				$name = $values['user_firstname'] . ' ' . $values['user_lastname'];
				break;
			case 'display_lastfirst':
				$name = $values['user_lastname'] . ' ' . $values['user_firstname'];
				break;
		}

		return $name;

	}
}
