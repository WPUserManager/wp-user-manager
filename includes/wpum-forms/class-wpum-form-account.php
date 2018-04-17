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
	 * Holds the currently logged in user ID.
	 *
	 * @var integer
	 */
	protected $user_id = null;

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
				$fields[ $this->get_parsed_id( $field->get_name(), $field->get_primary_id() ) ] = array(
					'label'       => $field->get_name(),
					'type'        => $field->get_type(),
					'required'    => $field->get_meta( 'required' ),
					'placeholder' => $field->get_meta( 'placeholder' ),
					'description' => $field->get_description(),
					'options'     => $this->get_field_dropdown_options( $field ),
					'priority'    => 0,
				);
			}

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

}
