<?php
/**
 * Handles generation of forms for WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Initialize all forms and prepare them to be handled by Vuejs.
 */
abstract class WPUM_Form {

	/**
	 * Form fields.
	 *
	 * @access protected
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Form action.
	 *
	 * @access protected
	 * @var string
	 */
	protected $action = '';

	/**
	 * Form errors.
	 *
	 * @access protected
	 * @var array
	 */
	protected $errors = array();

	/**
	 * Form steps.
	 *
	 * @access protected
	 * @var array
	 */
	protected $steps = array();

	/**
	 * Current form step.
	 *
	 * @access protected
	 * @var int
	 */
	protected $step = 0;

	/**
	 * Form name.
	 *
	 * @access protected
	 * @var string
	 */
	public $form_name = '';

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__ );
	}

	/**
	 * Unserializes instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__ );
	}

	/**
	 * Set properties of the class.
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set( $key, $value ) {
		$this->$key = $value;
	}

	/**
	 * Process the form.
	 *
	 * @return void
	 */
	public function process() {

		$step_key = $this->get_step_key( $this->step );

		if ( $step_key && is_callable( $this->steps[ $step_key ]['handler'] ) ) {
			call_user_func( $this->steps[ $step_key ]['handler'] );
		}

		$next_step_key = $this->get_step_key( $this->step );

		// if the step changed, but the next step has no 'view', call the next handler in sequence.
		if ( $next_step_key && $step_key !== $next_step_key && ! is_callable( $this->steps[ $next_step_key ]['view'] ) ) {
			$this->process();
		}

	}

	/**
	 * Calls the view handler if set, otherwise call the next handler.
	 *
	 * @param array $atts Attributes to use in the view handler.
	 */
	public function output( $atts = array() ) {
		$step_key = $this->get_step_key( $this->step );
		$this->show_errors();
		if ( $step_key && is_callable( $this->steps[ $step_key ]['view'] ) ) {
			call_user_func( $this->steps[ $step_key ]['view'], $atts );
		}
	}

	/**
	 * Adds an error.
	 *
	 * @param string $error The error message.
	 * @param string $error_code
	 */
	public function add_error( $error, $error_code = '' ) {
		$this->errors[] = apply_filters( 'wpum_form_error_message', $error, $error_code );
	}

	/**
	 * Displays errors.
	 */
	public function show_errors() {
		$errors = apply_filters( 'wpum_form_errors', $this->errors, $this->form_name );
		foreach ( $errors as $error ) {
			WPUM()->templates
			->set_template_data( [ 'message' => $error ] )
			->get_template_part( 'messages/general', 'error' );
		}
	}

	/**
	 * Gets the action (URL for forms to post to).
	 *
	 * @return string
	 */
	public function get_action() {
		return apply_filters( 'wpum_form_action', esc_url_raw( $this->action ? $this->action : wp_unslash( $_SERVER['REQUEST_URI'] ) ), $this );
	}

	/**
	 * Gets form name.
	 *
	 * @since 1.24.0
	 * @return string
	 */
	public function get_form_name() {
		return $this->form_name;
	}

	/**
	 * Gets steps from outside of the class.
	 *
	 * @return array
	 */
	public function get_steps() {
		return $this->steps;
	}

	/**
	 * Gets step from outside of the class.
	 */
	public function get_step() {
		return $this->step;
	}

	/**
	 * Gets step key from outside of the class.
	 *
	 * @param string|int $step
	 * @return string
	 */
	public function get_step_key( $step = '' ) {
		if ( ! $step ) {
			$step = $this->step;
		}
		$keys = array_keys( $this->steps );
		return isset( $keys[ $step ] ) ? $keys[ $step ] : '';
	}

	/**
	 * Sets step from outside of the class.
	 *
	 * @since 1.24.0
	 * @param int $step
	 */
	public function set_step( $step ) {
		$this->step = absint( $step );
	}

	/**
	 * Increases step from outside of the class.
	 */
	public function next_step() {
		$this->step ++;
	}

	/**
	 * Decreases step from outside of the class.
	 */
	public function previous_step() {
		$this->step --;
	}

	/**
	 * Gets fields for form.
	 *
	 * @param string $key
	 * @return array
	 */
	public function get_fields( $key ) {
		if ( empty( $this->fields[ $key ] ) ) {
			return array();
		}
		$fields = $this->fields[ $key ];
		uasort( $fields, array( $this, 'sort_by_priority' ) );
		return $fields;
	}

	/**
	 * Sorts array by priority value.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	protected function sort_by_priority( $a, $b ) {
	    if ( $a['priority'] == $b['priority'] ) {
	        return 0;
	    }
	    return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
	}

	/**
	 * Initializes form fields.
	 */
	protected function init_fields() {
		$this->fields = array();
	}

	/**
	 * Gets post data for fields.
	 *
	 * @return array of data
	 */
	protected function get_posted_fields() {
		$this->init_fields();
		$values = array();
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Get the value
				$field_type = str_replace( '-', '_', $field['type'] );

				$class = 'WPUM_Field_' . ucfirst( $field_type );

				if ( ! class_exists( $class ) ) {
					continue;
				}

				$field_object = new $class;

				if ( $handler = apply_filters( "wpum_get_posted_{$field_type}_field", false ) ) {
					$values[ $group_key ][ $key ] = call_user_func( $handler, $key, $field );
				} else {
					$values[ $group_key ][ $key ] = $field_object->get_posted_field( $key, $field );
				}
				// Set fields value
				$this->fields[ $group_key ][ $key ]['value'] = $values[ $group_key ][ $key ];
			}
		}
		return $values;
	}

	protected function str_len( $str ) {
		return mb_strlen( str_replace("\r\n", "\n", wp_specialchars_decode( wp_unslash( $str ) ) ) );
	}

	/**
	 * Validates the posted fields.
	 *
	 * @param array $values
	 * @throws Exception Uploaded file is not a valid mime-type or other validation error
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	protected function validate_fields( $values ) {
		foreach ( $this->fields as $group_key => $group_fields ) {
			foreach ( $group_fields as $key => $field ) {
				// Skip validation if field conditional logic not met.
				if ( apply_filters( 'wpum_form_skip_field_validation', false, $key, $values[ $group_key ], $group_fields ) ) {
					continue;
				}
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-user-manager' ), $field['label'] ) );
				}
				if ( isset( $field['maxlength'] ) && is_numeric( $field['maxlength'] ) && ( $this->str_len( $values[ $group_key ][ $key ] ) > $field['maxlength'] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s must not exceed %d characters', 'wp-user-manager' ), $field['label'], $field['maxlength'] ) );
				}
				if ( ! empty( $field['taxonomy'] ) && in_array( $field['type'], array( 'term-checklist', 'term-select', 'term-multiselect' ) ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = $values[ $group_key ][ $key ];
					} else {
						$check_value = empty( $values[ $group_key ][ $key ] ) ? array() : array( $values[ $group_key ][ $key ] );
					}
					foreach ( $check_value as $term ) {
						if ( ! term_exists( $term, $field['taxonomy'] ) ) {
							return new WP_Error( 'validation-error', sprintf( __( '%s is invalid', 'wp-user-manager' ), $field['label'] ) );
						}
					}
				}
				$template = isset( $field['template'] ) ? $field['template'] : $field['type'];
				if ( 'file' === $template && ! empty( $field['allowed_mime_types'] ) ) {
					$allowed_exts = explode( ',', $field['allowed_mime_types'] );
					$allowed_exts = array_map( 'trim', $allowed_exts );

					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( array( $values[ $group_key ][ $key ] ) );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							$file_url  = current( explode( '?', $file_url ) );
							$file_info = wp_check_filetype( $file_url );
							if ( ! is_numeric( $file_url ) && $file_info && ! in_array( $file_info['ext'], $allowed_exts ) ) {
								throw new Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-user-manager' ), $field['label'], $file_info['ext'], $allowed_exts ) );
							}
						}
					}
				}
			}
		}
		return apply_filters( 'submit_wpum_form_validate_fields', true, $this->fields, $values, $this->form_name, $this );
	}

	/**
	 * @param string $password
	 *
	 * @return bool|WP_Error
	 */
	protected function validate_strong_password( $password ) {
		if ( wpum_get_option( 'disable_strong_passwords' ) ) {
			return true;
		}

		$checkUppercase = apply_filters( 'wpum_strong_password_check_uppercase', true );
		$checkLetter     = apply_filters( 'wpum_strong_password_check_letter', false );
		$checkDigit     = apply_filters( 'wpum_strong_password_check_digit', true );
		$checkSpecial   = apply_filters( 'wpum_strong_password_check_special', true );

		$minLength   = apply_filters( 'wpum_strong_password_min_length', 8 );
		$checkLength = apply_filters( 'wpum_strong_password_check_length', $minLength > 0 );

		$error_message = array();
		if ( $checkUppercase ) {
			$error_message[] = __( '1 uppercase letter', 'wp-user-manager' );
		}

		if ( $checkLetter ) {
			$error_message[] = __( '1 letter', 'wp-user-manager' );
		}

		if ( $checkDigit ) {
			$error_message[] = __( '1 number', 'wp-user-manager' );
		}

		if ( $checkSpecial ) {
			$error_message[] = __( '1 special character', 'wp-user-manager' );
		}

		if ( ! empty( $error_message ) ) {
			$error_message = ' ' . __( 'and contain at least', 'wp-user-manager' ) . ' ' . implode( __( ' and ', 'wp-user-manager' ) , $error_message ) . '.';
		}

		$invalidMessage = apply_filters( 'wpum_strong_password_invalid_message', sprintf( __( 'Password must be at least %s characters long', 'wp-user-manager' ), $minLength ) . $error_message );

		$validates = true;

		if ( $validates && $checkUppercase && ! preg_match( '/[A-Z]/', $password ) ) {
			$validates = false;
		}

		if ( $validates && $checkLetter && ! preg_match( '/[a-zA-Z]/', $password ) ) {
			$validates = false;
		}

		if ( $validates && $checkDigit && ! preg_match( '/\d/', $password ) ) {
			$validates = false;
		}

		if ( $validates && $checkSpecial && ! preg_match( '/[^a-zA-Z\d]/', $password ) ) {
			$validates = false;
		}

		if ( $validates && $checkLength && strlen( $password ) < $minLength ) {
			$validates = false;
		}

		$validates = apply_filters( 'wpum_strong_password_is_valid', $validates, $password );

		if ( ! $validates ) {
			return new WP_Error( 'password-validation-error', esc_html( $invalidMessage ) );
		}

		return true;
	}

	/**
	 * Retrieve a name value for the form by replacing whitespaces with underscores
	 * and make everything lower case.
	 *
	 * If it's a primary field, get the primary id instead.
	 *
	 * @param string $name
	 * @param string $nicename
	 * @param object $field
	 *
	 * @return string
	 */
	protected function get_parsed_id( $name, $nicename, $field ) {

		if ( ! empty( $nicename ) ) {
			return str_replace( ' ', '_', strtolower( $nicename ) );
		} elseif ( empty( $nicename ) && $field->get_meta( 'user_meta_key' ) ) {
			return $field->get_meta( 'user_meta_key' );
		}

		return str_replace( ' ', '_', strtolower( $name ) );
	}

	/**
	 * Retrieve the list of options for the "Display name" field.
	 *
	 * @return array
	 */
	protected function get_displayname_options( $user ) {

		$options = array();

		// Generate the options
		$public_display                     = array();
		$public_display['display_username'] = $user->user_login;
		$public_display['display_nickname'] = $user->nickname;

		if ( ! empty( $user->first_name ) )
			$public_display['display_firstname'] = $user->first_name;
		if ( ! empty( $user->last_name ) )
			$public_display['display_lastname'] = $user->last_name;
		if ( ! empty( $user->first_name ) && ! empty( $user->last_name ) ) {
			$public_display['display_firstlast'] = $user->first_name . ' ' . $user->last_name;
			$public_display['display_lastfirst'] = $user->last_name . ' ' . $user->first_name;
		}

		if ( ! in_array( $user->display_name, $public_display ) ) {
			$public_display = array( 'display_displayname' => $user->display_name ) + $public_display;
			$public_display = array_map( 'trim', $public_display );
			$public_display = array_unique( $public_display );
		}

		// Add options to original array
		foreach ( $public_display as $id => $item ) {
			$options[ $id ] = $item;
		}

		return $options;

	}

	/**
	 * Retrieve a list of dropdown options for a given field.
	 *
	 * @param object $field
	 * @return array
	 */
	protected function get_field_dropdown_options( $field, $user ) {

		$options = [];

		if ( ! empty( $field->get_primary_id() ) ) {
			switch ( $field->get_primary_id() ) {
				case 'user_displayname':
					$options = $this->get_displayname_options( $user );
					break;
			}
		} elseif ( ! $field->is_primary() && strpos( $field->get_meta( 'user_meta_key' ), 'wpum_' ) === 0 ) {
			$stored_options = $field->get_meta( 'dropdown_options' );
			if ( ! empty( $stored_options ) && is_array( $stored_options ) ) {
				foreach ( $stored_options as $option ) {
					$options[ $option['value'] ] = $option['label'];
				}
			}
		}

		return apply_filters( 'wpum_form_custom_field_dropdown_options', $options, $field );
	}

	/**
	 * Retrieve stored dropdown options from the db.
	 *
	 * @param object $field
	 *
	 * @return array
	 */
	protected function get_custom_field_dropdown_options( $field ) {
		if ( $field->is_primary() ) {
			return [];
		}

		$options = [];

		$allowed_types = [ 'dropdown', 'multiselect', 'radio', 'multicheckbox' ];

		if ( in_array( $field->get_type(), $allowed_types ) ) {
			$stored_options = $field->get_meta( 'dropdown_options' );
			if ( ! empty( $stored_options ) && is_array( $stored_options ) ) {
				foreach ( $stored_options as $option ) {
					$options[ $option['value'] ] = $option['label'];
				}
			}
		}

		return apply_filters( 'wpum_form_custom_field_dropdown_options', $options, $field );
	}

}
