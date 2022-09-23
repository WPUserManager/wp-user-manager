<?php
/**
 * Handles prevention of password change for users.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The class that handles the password change prevention.
 */
class WPUM_Prevent_Password_Change {

	/**
	 * Get things started.
	 */
	public function __construct() {

		add_action( 'carbon_fields_register_fields', array( $this, 'register_custom_field' ) );
		add_filter( 'submit_wpum_form_validate_fields', array( $this, 'prevent_change' ), 10, 4 );

	}

	/**
	 * Register the checkbox custom field in the admin panel.
	 *
	 * @return void
	 */
	public function register_custom_field() {
		Container::make( 'user_meta', esc_html__( 'Prevent password change', 'wp-user-manager' ) )
			->add_fields( array(
				Field::make( 'checkbox', 'prevent_password_change', esc_html__( 'Prevent password change', 'wp-user-manager' ) )
					->set_help_text( esc_html__( 'Enable to prevent this user from changing his password from the front-end.', 'wp-user-manager' ) ),
			) );
	}

	/**
	 * Prevent password change during validation process of the form.
	 *
	 * @param bool   $pass
	 * @param array  $fields
	 * @param array  $values
	 * @param string $form
	 *
	 * @return boolean
	 */
	public function prevent_change( $pass, $fields, $values, $form ) {

		if ( 'password' === $form && isset( $values['password']['password'] ) ) {

			$user_id = get_current_user_id();

			if ( \WPUM\carbon_get_user_meta( $user_id, 'prevent_password_change' ) ) {
				return new WP_Error( 'password-validation-error', esc_html__( 'Changing password for this account is currently disabled.', 'wp-user-manager' ) );
			}
		}

		return $pass;

	}

}

new WPUM_Prevent_Password_Change();
