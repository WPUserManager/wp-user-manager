<?php
/**
 * Registers a repeater field for the forms.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register a text field type.
 */
class WPUM_Field_Repeater extends WPUM_Field_Type {

	public function __construct() {
		$this->group = 'advanced';
		$this->name  = esc_html__( 'Repeater', 'wp-user-manager' );
		$this->type  = 'repeater';
		$this->template = 'complex';
		$this->icon  = 'dashicons-menu-alt';
		$this->order = 1;

		add_filter( 'wpum_fields_editor_deregister_model', array( $this, 'parent_field_model_data' ), 10, 2 );
		add_filter( 'wpum_register_field_type_settings', array( $this, 'settings_fields' ), 10, 2 );
		add_filter( 'wpum_registered_parent_field_types', array( $this, 'register_parent_field' ) );
	}

	/**
	 * @return array
	 */
	public function get_editor_settings() {
		return [
			'general' => [
				'button_label' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Button Label', 'wp-user-manager' ),
					'model'     => 'button_label',
					'default' 	=> esc_html__( 'Add row', 'wp-user-manager' ),
					'hint'      => esc_html__( 'Enter the button label of add row.', 'wp-user-manager' ),
				)
			],
			'validation' => [
				'min_rows' 	=> array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Minimum Rows', 'wp-user-manager' ),
					'model'     => 'min_rows',
					'hint'      => esc_html__( 'Enter the minimum rows required for the field.', 'wp-user-manager' ),
				),
				'max_rows' 	=> array(
					'type'      => 'input',
					'inputType' => 'number',
					'label'     => esc_html__( 'Maximum Rows', 'wp-user-manager' ),
					'model'     => 'max_rows',
					'hint'      => esc_html__( 'Enter the maximum rows for the field.', 'wp-user-manager' ),
				)
			],
			'fields' => [
				'repeater' => array(
					'type'      => 'repeater',
					'model'     => 'repeater',
				)
			],
		];
	}

	/**
	 * Setup settings fields
	 *
	 * @param array $fields
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
	 * @param array $model
	 * @param int $primary_field_id
	 *
	 * @return array
	 */
	public function parent_field_model_data( $model, $primary_field_id ) {
		if ( isset( $model['repeater'] ) ) {
			$field_id = intval( $_POST['field_id'] );
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
	public function register_parent_field( $fields ){
		$fields[] = $this->type;

		return $fields;
	}

	/**
	 * @param object $field
	 * @param mixed  $values
	 *
	 * @return string
	 */
	function get_formatted_output( $field, $values ) {
		$html = '';

		$children = WPUM()->fields->get_fields([
			'group_id' => $field->get_group_id(),
			'parent'   => $field->get_ID(),
			'order'	   => 'ASC'
		]);

		foreach( $values as $value ){
			$html .= '<ul class="field_repeater_child">';
			foreach ( $children as $child ) {
				if ( $child->get_visibility() !== 'public' ) {
					continue;
				}
				if ( isset( $value[ $child->get_key() ] ) ) {
					$formatted = $child->field_type->get_formatted_output( $child, $value[ $child->get_key() ] );
					$html      .= sprintf( '<li><strong>%s</strong>: %s</li>', $child->get_name(), $formatted );
				}
			}
			$html .= '</ul>';
		}

		return $html;
	}
}
