<?php
/**
 * Handles the WPUM own login form.
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
 * WPUM_Form_Login
 */
class WPUM_Form_Login extends WPUM_Form {

	/**
	 * Form name.
	 *
	 * @var string
	 */
	public $form_name = 'login';

	/**
	 * Determine if there's a referrer.
	 *
	 * @var mixed
	 */
	protected $referrer;

	/**
	 * Stores static instance of class.
	 *
	 * @var WPUM_Form_Login The single instance of the class
	 */
	protected static $instance = null;

	/**
	 * Returns static instance of class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'process' ) );

		$this->steps = (array) apply_filters( 'login_steps', array(
			'submit' => array(
				'name'     => __( 'Login Details', 'wp-user-manager' ),
				'view'     => array( $this, 'submit' ),
				'handler'  => array( $this, 'submit_handler' ),
				'priority' => 10,
			),
			'done'   => array(
				'name'     => __( 'Done', 'wp-user-manager' ),
				'view'     => false,
				'handler'  => array( $this, 'done' ),
				'priority' => 30,
			),
		) );

		$this->sort_set_steps();

	}

	/**
	 * Initializes the fields used in the form.
	 */
	public function init_fields() {
		if ( $this->fields ) {
			return;
		}

		$this->fields = apply_filters( 'login_form_fields', array(
			'login' => array(
				'username' => array(
					'label'       => wpum_get_login_label(),
					'type'        => 'text',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 1,
				),
				'password' => array(
					'label'       => __( 'Password', 'wp-user-manager' ),
					'type'        => 'password',
					'required'    => true,
					'placeholder' => '',
					'priority'    => 2,
				),
				'remember' => array(
					'label'    => __( 'Remember me', 'wp-user-manager' ),
					'type'     => 'checkbox',
					'required' => false,
					'priority' => 3,
				),
			),
		) );

	}

	/**
	 * Show the form.
	 *
	 * @return void
	 */
	public function submit() {

		$this->init_fields();

		$data = array(
			'form'   => $this->form_name,
			'action' => $this->get_action(),
			'fields' => $this->get_fields( 'login' ),
			'step'   => $this->get_step(),
		);

		WPUM()->templates
			->set_template_data( $data )
			->get_template_part( 'forms/form', 'login' );

	}

	/**
	 * Handle submission of the form.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function submit_handler() {
		try {

			$this->init_fields();

			$values = $this->get_posted_fields();

			if ( empty( $_POST['submit_login'] ) ) { // phpcs:ignore
				return;
			}

			$return = $this->validate_fields( $values );
			if ( is_wp_error( $return ) ) {
				throw new Exception( $return->get_error_message() );
			}

			$username = $values['login']['username'];
			$password = $values['login']['password'];

			$authenticate = wp_authenticate( $username, $password );

			if ( is_wp_error( $authenticate ) ) {

				throw new Exception( $authenticate->get_error_message() );

			} elseif ( $authenticate instanceof WP_User ) {

				$this->user_id = $authenticate->data->ID;

			}

			// Successful, show next step.
			$this->step ++;

		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'login_submit' );
			return;
		}
	}

	/**
	 * Sign the user in.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function done() {

		try {

			$values   = $this->get_posted_fields();
			$username = $values['login']['username'];
			$password = $values['login']['password'];

			$creds = array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => $values['login']['remember'],
			);

			$redirect = get_permalink( wpum_get_core_page_id( 'login' ) );

			$login_redirect = wpum_get_login_redirect();
			if ( ! empty( $login_redirect ) ) {
				$redirect = $login_redirect;
			}

			$redirect_to = filter_input( INPUT_GET, 'redirect_to' );
			if ( $redirect_to ) {
				$redirect = $redirect_to;
			}

			do_action( 'wpum_before_login', $username );

			$user = wp_signon( $creds );

			wp_set_current_user( $user->ID );

			do_action( 'wpum_after_login', $user->ID, $user );

			$redirect = apply_filters( 'wpum_redirect_after_login', $redirect, $user );

			if ( is_wp_error( $user ) ) {
				throw new Exception( $user->get_error_message() );
			} else {
				wp_safe_redirect( $redirect );
				exit;
			}
		} catch ( Exception $e ) {
			$this->add_error( $e->getMessage(), 'login_done' );
			return;
		}

	}

}
