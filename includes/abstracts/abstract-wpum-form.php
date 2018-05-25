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
	 */
	public function add_error( $error ) {
		$this->errors[] = $error;
	}

	/**
	 * Displays errors.
	 */
	public function show_errors() {
		foreach ( $this->errors as $error ) {
			echo '<div class="wpum-message error">' . $error . '</div>';
		}
	}

	/**
	 * Gets the action (URL for forms to post to).
	 *
	 * @return string
	 */
	public function get_action() {
		return esc_url_raw( $this->action ? $this->action : wp_unslash( $_SERVER['REQUEST_URI'] ) );
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
				if ( $handler = apply_filters( "wpum_get_posted_{$field_type}_field", false ) ) {
					$values[ $group_key ][ $key ] = call_user_func( $handler, $key, $field );
				} elseif ( method_exists( $this, "get_posted_{$field_type}_field" ) ) {
					$values[ $group_key ][ $key ] = call_user_func( array( $this, "get_posted_{$field_type}_field" ), $key, $field );
				} else {
					$values[ $group_key ][ $key ] = $this->get_posted_field( $key, $field );
				}
				// Set fields value
				$this->fields[ $group_key ][ $key ]['value'] = $values[ $group_key ][ $key ];
			}
		}
		return $values;
	}

	/**
	 * Navigates through an array and sanitizes the field.
	 *
	 * @param array|string    $value      The array or string to be sanitized.
	 * @param string|callable $sanitizer  The sanitization method to use. Built in: `url`, `email`, `url_or_email`, or
	 *                                      default (text). Custom single argument callable allowed.
	 * @return array|string   $value      The sanitized array (or string from the callback).
	 */
	protected function sanitize_posted_field( $value, $sanitizer = null ) {
		// Sanitize value
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $val ) {
				$value[ $key ] = $this->sanitize_posted_field( $val, $sanitizer );
			}
			return $value;
		}
		$value = trim( $value );
		if ( 'url' === $sanitizer ) {
			return esc_url_raw( $value );
		} elseif ( 'email' === $sanitizer ) {
			return sanitize_email( $value );
		} elseif ( 'url_or_email' === $sanitizer ) {
			if ( null !== parse_url( $value, PHP_URL_HOST ) ) {
				// Sanitize as URL
				return esc_url_raw( $value );
			}
			// Sanitize as email
			return sanitize_email( $value );
		} elseif ( is_callable( $sanitizer ) ) {
			return call_user_func( $sanitizer, $value );
		}
		// Use standard text sanitizer
		return sanitize_text_field( stripslashes( $value ) );
	}

	/**
	 * Gets the value of a posted field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string|array
	 */
	protected function get_posted_field( $key, $field ) {
		// Allow custom sanitizers with standard text fields.
		if ( ! isset( $field['sanitizer'] ) ) {
			$field['sanitizer'] = null;
		}
		return isset( $_POST[ $key ] ) ? $this->sanitize_posted_field( $_POST[ $key ], $field['sanitizer'] ) : '';
	}

	/**
	 * Gets the value of a posted multiselect field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return array
	 */
	protected function get_posted_multiselect_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? array_map( 'sanitize_text_field', $_POST[ $key ] ) : array();
	}

	/**
	 * Gets the value of a posted file field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string|array
	 */
	protected function get_posted_file_field( $key, $field ) {
		$file = $this->upload_file( $key, $field );
		if ( ! $file ) {
			$file = $this->get_posted_field( 'current_' . $key, $field );
		} elseif ( is_array( $file ) ) {
			$file = array_filter( array_merge( $file, (array) $this->get_posted_field( 'current_' . $key, $field ) ) );
		}
		return $file;
	}

	/**
	 * Gets the value of a posted textarea field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	protected function get_posted_textarea_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? wp_kses_post( trim( stripslashes( $_POST[ $key ] ) ) ) : '';
	}

	/**
	 * Gets the value of a posted textarea field.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return string
	 */
	protected function get_posted_wp_editor_field( $key, $field ) {
		return $this->get_posted_textarea_field( $key, $field );
	}

	/**
	 * Gets posted terms for the taxonomy.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return array
	 */
	protected function get_posted_term_checklist_field( $key, $field ) {
		if ( isset( $_POST[ 'tax_input' ] ) && isset( $_POST[ 'tax_input' ][ $field['taxonomy'] ] ) ) {
			return array_map( 'absint', $_POST[ 'tax_input' ][ $field['taxonomy'] ] );
		} else {
			return array();
		}
	}

	/**
	 * Gets posted terms for the taxonomy.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return int
	 */
	protected function get_posted_term_multiselect_field( $key, $field ) {
		return isset( $_POST[ $key ] ) ? array_map( 'absint', $_POST[ $key ] ) : array();
	}

	/**
	 * Gets posted terms for the taxonomy.
	 *
	 * @param  string $key
	 * @param  array  $field
	 * @return int
	 */
	protected function get_posted_term_select_field( $key, $field ) {
		return ! empty( $_POST[ $key ] ) && $_POST[ $key ] > 0 ? absint( $_POST[ $key ] ) : '';
	}

	/**
	 * Handles the uploading of files.
	 *
	 * @param string $field_key
	 * @param array  $field
	 * @throws Exception When file upload failed
	 * @return  string|array
	 */
	protected function upload_file( $field_key, $field ) {
		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ]['name'] ) ) {
			if ( ! empty( $field['allowed_mime_types'] ) ) {
				$allowed_mime_types = $field['allowed_mime_types'];
			} else {
				$allowed_mime_types = wpum_get_allowed_mime_types();
			}
			$file_urls       = array();
			$files_to_upload = wpum_prepare_uploaded_files( $_FILES[ $field_key ] );
			foreach ( $files_to_upload as $file_to_upload ) {

				$uploaded_file = wpum_upload_file( $file_to_upload, array(
					'file_key'           => $field_key,
					'allowed_mime_types' => $allowed_mime_types,
				) );
				// Determine max file size for the avatar field.
				$too_big_message = esc_html__( 'The uploaded file is too big.', 'wp-user-manager' );
				if ( defined( 'WPUM_MAX_AVATAR_SIZE' ) && $field_key == 'user_avatar' && $file_to_upload['size'] > WPUM_MAX_AVATAR_SIZE ) {
					throw new Exception( $too_big_message );
				}
				if ( defined( 'WPUM_MAX_COVER_SIZE' ) && $field_key == 'user_cover' && $file_to_upload['size'] > WPUM_MAX_COVER_SIZE ) {
					throw new Exception( $too_big_message );
				}
				if ( is_wp_error( $uploaded_file ) ) {
					throw new Exception( $uploaded_file->get_error_message() );
				} else {
					$file_urls[] = [
						'url'  => $uploaded_file->url,
						'path' => $uploaded_file->file
					];
				}
			}
			if ( ! empty( $field['multiple'] ) ) {
				return $file_urls;
			} else {
				return current( $file_urls );
			}
		}
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
				if ( $field['required'] && empty( $values[ $group_key ][ $key ] ) ) {
					return new WP_Error( 'validation-error', sprintf( __( '%s is a required field', 'wp-user-manager' ), $field['label'] ) );
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
				if ( 'file' === $field['type'] && ! empty( $field['allowed_mime_types'] ) ) {
					if ( is_array( $values[ $group_key ][ $key ] ) ) {
						$check_value = array_filter( $values[ $group_key ][ $key ] );
					} else {
						$check_value = array_filter( array( $values[ $group_key ][ $key ] ) );
					}
					if ( ! empty( $check_value ) ) {
						foreach ( $check_value as $file_url ) {
							$file_url  = current( explode( '?', $file_url ) );
							$file_info = wp_check_filetype( $file_url );
							if ( ! is_numeric( $file_url ) && $file_info && ! in_array( $file_info['type'], $field['allowed_mime_types'] ) ) {
								throw new Exception( sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s', 'wp-user-manager' ), $field['label'], $file_info['ext'], implode( ', ', array_keys( $field['allowed_mime_types'] ) ) ) );
							}
						}
					}
				}
			}
		}
		return apply_filters( 'submit_wpum_form_validate_fields', true, $this->fields, $values, $this->form_name );
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
	 * @return void
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

		return $options;

	}

	/**
	 * Retrieve stored dropdown options from the db.
	 *
	 * @param object $field
	 * @return void
	 */
	protected function get_custom_field_dropdown_options( $field ) {

		if ( $field->is_primary() ) {
			return;
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

		return $options;

	}

}
