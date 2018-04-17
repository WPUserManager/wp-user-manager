<?php
/**
 * Handles the WPUM account page.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Form_Account extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'account';

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

		$this->user = wp_get_current_user();

		add_action( 'wp', array( $this, 'process' ) );

		$this->steps  = (array) apply_filters( 'wpum_account_tabs', array(
			'account' => array(
				'name'     => esc_html__( 'Edit account' ),
				'view'     => array( $this, 'show_form' ),
				'handler'  => array( $this, 'account_handler' ),
				'priority' => 10
			),
			'password' => array(
				'name'     => esc_html__( 'Change password' ),
				'view'     => array( $this, 'show_form' ),
				'handler'  => array( $this, 'password_handler' ),
				'priority' => 11
			),
			'logout' => array(
				'name'     => esc_html__( 'Logout' ),
				'view'     => array( $this, 'logout_view' ),
				'handler'  => false,
				'priority' => 12
			)
		) );

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

		$this->fields = apply_filters( 'account_page_form_fields', array(
			'account'  => $this->get_account_fields(),
			'password' => [],
		) );

	}

	/**
	 * Retrieve the list of fields for the account page.
	 *
	 * @return array
	 */
	private function get_account_fields() {

		$fields         = [];
		$primary_group  = WPUM()->fields_groups->get_groups( [ 'primary' => true ] );
		$primary_group  = $primary_group[0];
		$account_fields = WPUM()->fields->get_fields( [
			'group_id' => $primary_group->get_ID(),
			'orderby'  => 'field_order',
			'order'    => 'ASC'
		] );

		foreach ( $account_fields as $field ) {

			$field = new WPUM_Field( $field );

			if( $field->exists() && $field->get_meta( 'editing' ) == 'public' && $field->get_primary_id() !== 'user_password' ) {

				// Skip the avatar field if disabled.
				if( $field->get_primary_id() == 'user_avatar' && ! wpum_get_option( 'custom_avatars' ) ) {
					continue;
				}

				$fields[ $this->get_parsed_id( $field->get_name(), $field->get_primary_id() ) ] = array(
					'label'       => $field->get_name(),
					'type'        => $field->get_type(),
					'required'    => $field->get_meta( 'required' ),
					'placeholder' => $field->get_meta( 'placeholder' ),
					'description' => $field->get_description(),
					'options'     => $this->get_field_dropdown_options( $field, $this->user ),
					'value'       => $this->get_user_field_value( $field ),
					'priority'    => 0,
				);
			}

		}

		return $fields;

	}

	/**
	 * Retrieve the value of a given field for the currently logged in user.
	 *
	 * @param object $field
	 * @return void
	 */
	private function get_user_field_value( $field ) {

		$value = false;

		if( ! empty( $field->get_primary_id() ) ) {

			switch ( $field->get_primary_id() ) {
				case 'user_firstname':
					$value = esc_html( $this->user->user_firstname );
					break;
				case 'user_lastname':
					$value = esc_html( $this->user->user_lastname );
					break;
				case 'user_email':
					$value = esc_html( $this->user->user_email );
					break;
				case 'user_nickname':
					$value = esc_html( get_user_meta( $this->user->ID, 'nickname', true ) );
					break;
				case 'user_website':
					$value = esc_html( $this->user->user_url );
					break;
				case 'user_description':
					$value = esc_textarea( get_user_meta( $this->user->ID, 'description', true ) );
					break;
				case 'user_displayname':
					$value = $this->get_selected_displayname();
					break;
				case 'user_avatar':
					//$value = get_user_meta( $this->user->ID, 'current_user_avatar', true );
					$value = carbon_get_user_meta( $this->user->ID, 'current_user_avatar' );
					break;
			}

		} else {

			$value = esc_html( get_user_meta( $this->user->ID, $field->get_meta( 'user_meta_key' ), true ) );

		}

		return $value;

	}

	/**
	 * Display the account form.
	 *
	 * @return void
	 */
	public function show_form() {

		$this->init_fields();

		$data = [
			'form'    => $this->form_name,
			'action'  => $this->get_action(),
			'fields'  => $this->get_fields( $this->get_step_key( $this->get_step() ) ),
			'step'    => $this->get_step(),
			'steps'   => $this->get_steps(),
			'current' => $this->get_step_key( $this->get_step() ),
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

			if ( ! isset( $_POST['current-form'] ) || $_POST['current-form'] !== 'account' ) {
				return;
			}

			if( ! wp_verify_nonce( $_POST['account_' . $this->get_step_key( $this->get_step() ) . '_nonce' ], 'verify_account_form' ) ) {
				return;
			}

			if ( empty( $_POST['submit_account'] ) ) {
				return;
			}

			if ( is_wp_error( ( $return = $this->validate_fields( $values ) ) ) ) {
				throw new Exception( $return->get_error_message() );
			}

			// Collect all the data to update the user.
			$user_data = [
				'ID' => $this->user->ID
			];

			// Update first name and last name.
			if( isset( $values['account']['user_firstname'] ) ) {
				$user_data[ 'first_name' ] = $values['account']['user_firstname'];
			}
			if( isset( $values['account']['user_lastname'] ) ) {
				$user_data[ 'last_name' ] = $values['account']['user_lastname'];
			}

			// Update email address.
			if( isset( $values['account']['user_email'] ) ) {
				$user_data[ 'user_email' ] = $values['account']['user_email'];
			}

			// Update nickname.
			if( isset( $values['account']['user_nickname'] ) ) {
				$user_data[ 'nickname' ] = $values['account']['user_nickname'];
			}

			// Update website.
			if( isset( $values['account']['user_website'] ) ) {
				$user_data[ 'user_url' ] = $values['account']['user_website'];
			}

			// Update description.
			if( isset( $values['account']['user_description'] ) ) {
				$user_data[ 'description' ] = $values['account']['user_description'];
			}

			// Update displayed name.
			if( isset( $values['account']['user_displayname'] ) ) {
				$user_data[ 'display_name' ] = $this->parse_displayname( $values['account'], $values['account']['user_displayname'] );
			}

			// Now update the user.
			$updated_user_id = wp_update_user( $user_data );

			if( is_wp_error( $updated_user_id ) ) {
				throw new Exception( $updated_user_id->get_error_message() );
			}

			// Now update the avatar for the user.
			if( wpum_get_option( 'custom_avatars' ) && isset( $values['account']['user_avatar'] ) ) {
				carbon_set_user_meta( $updated_user_id, 'current_user_avatar', $values['account']['user_avatar'] );
			}

			do_action( 'wpum_after_user_update', $this, $values, $updated_user_id );

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}

	}

	/**
	 * Prepare the correct value for the display name option.
	 *
	 * @param array $values
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

	/**
	 * Retrieve the option currently selected for the display name setting.
	 *
	 * @return string
	 */
	private function get_selected_displayname() {

		$selected_name  = $this->user->display_name;
		$user_login     = $this->user->user_login;
		$nickname       = $this->user->nickname;
		$first_name     = $this->user->first_name;
		$last_name      = $this->user->last_name;
		$firstlast      = $this->user->first_name . ' ' . $this->user->last_name;
		$lastfirst      = $this->user->last_name . ' ' . $this->user->first_name;
		$selected_value = $user_login;

		switch ( $selected_name ) {
			case $nickname:
				$selected_value = 'display_nickname';
				break;
			case $first_name:
				$selected_value = 'display_firstname';
				break;
			case $last_name:
				$selected_value = 'display_lastname';
				break;
			case $firstlast:
				$selected_value = 'display_firstlast';
				break;
			case $lastfirst:
				$selected_value = 'display_lastfirst';
				break;
			default:
				$selected_value = $user_login;
				break;
		}

		return $selected_value;

	}

}
