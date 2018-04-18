<?php
/**
 * Handles the WPUM own password changing for for currently logged in users.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Form_Password extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'password';

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

		if( ! is_user_logged_in() ) {
			return;
		}

		add_action( 'wp', array( $this, 'process' ) );

		$this->steps  = (array) apply_filters( 'password_change_steps', array(
			'submit' => array(
				'name'     => false,
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10
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

		$this->fields = apply_filters( 'password_change_form_fields', array(
			'password' => array(
				'password' => array(
					'label'       => esc_html__( 'Password' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 0
				),
				'password_repeat' => array(
					'label'       => esc_html__( 'Repeat password' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 0
				),
			)
		) );

	}

	/**
	 * Show the form.
	 *
	 * @return void
	 */
	public function submit() {

		$this->init_fields();

		$data = [
			'form'   => $this->form_name,
			'action' => $this->get_action(),
			'fields' => $this->get_fields( 'password' ),
			'step'   => $this->get_step()
		];

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'password' );

	}

	/**
	 * Handle submission of the form.
	 *
	 * @return void
	 */
	public function submit_handler() {

		try {

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage() );
			return;
		}

	}

}
