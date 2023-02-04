<?php
/**
 * Registers a repeater field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register a text field type.
 */
class WPUM_Field_Repeater extends WPUM_Field_Type {

	/**
	 * Construct
	 */
	public function __construct() {
		$this->group             = 'advanced';
		$this->name              = esc_html__( 'Repeater', 'wp-user-manager' );
		$this->type              = 'repeater';
		$this->template          = 'complex';
		$this->icon              = 'dashicons-menu-alt';
		$this->order             = 1;
		$this->min_addon_version = '2.2';

		add_filter( 'wpum_fields_editor_deregister_model', array( $this, 'parent_field_model_data' ), 10, 2 );
		add_filter( 'wpum_register_field_type_settings', array( $this, 'settings_fields' ), 10, 2 );
		add_filter( 'wpum_registered_parent_field_types', array( $this, 'register_parent_field' ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return array(
			'general'    => array(
				'button_label' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Button Label', 'wp-user-manager' ),
					'model'     => 'button_label',
					'default'   => esc_html__( 'Add row', 'wp-user-manager' ),
					'hint'      => esc_html__( 'Enter the button label of add row.', 'wp-user-manager' ),
				),
			),
			'validation' => array(
				'min_rows' => array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Minimum Rows', 'wp-user-manager' ),
					'model'     => 'min_rows',
					'hint'      => esc_html__( 'Enter the minimum rows required for the field.', 'wp-user-manager' ),
				),
				'max_rows' => array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Maximum Rows', 'wp-user-manager' ),
					'model'     => 'max_rows',
					'hint'      => esc_html__( 'Enter the maximum rows for the field.', 'wp-user-manager' ),
				),
			),
			'fields'     => array(
				'repeater' => array(
					'type'  => 'repeater',
					'model' => 'repeater',
				),
			),
		);
	}

	/**
	 * Setup settings fields
	 *
	 * @param array  $fields
	 * @param string $type
	 *
	 * @return array
	 */
	public function settings_fields( $fields, $type ) {
		if ( $type === $this->type ) {

			if ( isset( $fields['general']['placeholder'] ) ) {
				unset( $fields['general']['placeholder'] );
			}
		}

		return $fields;
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
				if ( false !== strpos( $key, 'wpum_field' ) ) {
					$parts = explode( '_', $key );
					if ( count( $parts ) > 3 ) {
						array_pop( $parts );
						$key = implode( '_', $parts );
					}
				}

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
			if ( null !== wp_parse_url( $value, PHP_URL_HOST ) ) {
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
	 * @param array $model
	 * @param int   $primary_field_id
	 *
	 * @return array
	 */
	public function parent_field_model_data( $model, $primary_field_id ) {
		if ( isset( $model['repeater'] ) ) {
			$field_id = filter_input( INPUT_POST, 'field_id', FILTER_VALIDATE_INT );
			$field    = new WPUM_Field( $field_id );

			$model['parent'] = $field->get_ID();
			$model['group']  = $field->get_group_id();
		}

		return $model;
	}


	/**
	 * Register as parent field
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function register_parent_field( $fields ) {
		$fields[] = $this->type;

		return $fields;
	}

	/**
	 * @param object $field
	 * @param mixed  $values
	 *
	 * @return string
	 */
	public function get_formatted_output( $field, $values ) {
		$html = '';

		$children = WPUM()->fields->get_fields(array(
			'group_id' => $field->get_group_id(),
			'parent'   => $field->get_ID(),
			'order'    => 'ASC',
		));

		foreach ( $values as $value ) {
			$html .= '<ul class="field_repeater_child">';
			foreach ( $children as $child ) {
				if ( $child->get_visibility() !== 'public' ) {
					continue;
				}

				if ( isset( $value[ $child->get_key() ] ) && ! empty( $value[ $child->get_key() ] ) ) {
					$formatted = $child->field_type->get_formatted_output( $child, $value[ $child->get_key() ] );
					$html     .= sprintf( '<li><strong>%s</strong>: %s</li>', $child->get_name(), $formatted );
				}
			}
			$html .= '</ul>';
		}

		return $html;
	}

		/**
		 * Gets the value of a posted file field.
		 *
		 * @param string $key
		 * @param array  $field
		 *
		 * @return string|array
		 * @throws Exception
		 */
	public function get_posted_field( $key, $field ) {
		// Allow custom sanitizers with standard text fields.
		if ( ! isset( $field['sanitizer'] ) ) {
			$field['sanitizer'] = null;
		}

		$posted = isset( $_POST[ $key ] ) ? $this->sanitize_posted_field( $_POST[ $key ], $field['sanitizer'] ) : array(); // phpcs:ignore

		if ( ! isset( $_FILES[ $key ] ) ) {
			return $posted;
		}

		if ( isset( $_FILES[ $key ] ) && ! empty( $_FILES[ $key ] ) ) {
			$files = $this->upload_file( $key, $field );
		}

		$current_repeater = isset( $_POST[ 'current_' . $key ] ) ? $this->sanitize_posted_field( $_POST[ 'current_' . $key ], $field['sanitizer'] ) : array(); // phpcs:ignore

		foreach ( $_FILES[ $key ]['name'] as $index => $post ) { // phpcs:ignore
			$post_keys = array_keys( $post );
			foreach ( $post_keys as $key ) {
				$file = isset( $files[ $index ][ $key ] ) ? $files[ $index ][ $key ] : '';
				if ( empty( $file ) ) {
					$file = isset( $current_repeater[ $index ][ $key ] ) ? $current_repeater[ $index ][ $key ] : '';
				}
				$posted[ $index ][ $key ] = $file;
			}
		}

		return $posted;
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
		if ( isset( $_FILES[ $field_key ] ) && ! empty( $_FILES[ $field_key ] ) ) {
			$allowed_mime_types = wpum_get_allowed_mime_types();
			$files              = array();

			foreach ( $_FILES[ $field_key ] as $file_key => $file ) { // phpcs:ignore
				array_walk_recursive( $file, function ( $item, $key, $file_key ) use ( &$results ) {
					$results[ $key ][ $file_key ][] = $item;
				}, $file_key );
			}

			$file_urls = array();
			foreach ( $results as $primary_key => $upload ) {

				$id         = str_replace( 'wpum_field_file_', '', $primary_key );
				$file_field = new WPUM_Field( $id );

				$field_name       = $file_field->get_name();
				$field_max_size   = $file_field->get_meta( 'max_file_size' );
				$field_mime_types = $file_field->get_meta( 'allowed_mime_types' );

				if ( ! empty( $field_mime_types ) ) {
					$extensions         = explode( ',', $field_mime_types );
					$allowed_mime_types = array();
					foreach ( $extensions as $extension ) {
						$extension = strtolower( trim( str_replace( '.', '', $extension ) ) );
						foreach ( get_allowed_mime_types() as $allowed_ext => $allowed_mime_type ) {
							if ( in_array( $extension, explode( '|', $allowed_ext ), true ) ) {
								$allowed_mime_types[ $allowed_ext ] = $allowed_mime_type;
								break;
							}
						}
					}
				}

				$cloned_keys = array();
				foreach ( $upload['name'] as $key => $name ) {
					if ( ! empty( $name ) ) {
						$cloned_keys[] = $key;
					}
				}

				$files_to_upload = wpum_prepare_uploaded_files( $upload );
				foreach ( $files_to_upload as $field_key => $file_to_upload ) {
					// translators: %s field name
					$too_big_message = sprintf( esc_html__( 'The uploaded %s file is too big.', 'wp-user-manager' ), $field_name );

					if ( ! empty( $field_max_size ) && $file_to_upload['size'] > $field_max_size ) {
						throw new Exception( $too_big_message );
					}

					$uploaded_file = wpum_upload_file( $file_to_upload, array(
						'file_key'           => $primary_key,
						'allowed_mime_types' => $allowed_mime_types,
						'file_label'         => $field_name,
					) );

					if ( is_wp_error( $uploaded_file ) ) {
						throw new Exception( $uploaded_file->get_error_message() );
					} else {
						$file_urls[ $cloned_keys[ $field_key ] ][ $primary_key ] = $uploaded_file->url;
					}
				}
			}

			return $file_urls;
		}

		return '';
	}
}
