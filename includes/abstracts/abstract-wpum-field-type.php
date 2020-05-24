<?php
/**
 * Handles definition of all the field types supported by WPUM.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main class that handles registration of field types.
 */
abstract class WPUM_Field_Type {

	/**
	 * Full name of the field type, eg "Paragraph Text".
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $name;

	/**
	 * Type of the field, eg "textarea".
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $type;

	/**
	 * Used to if the child type is different to parent, but uses same template
	 *
	 * @var string
	 */
	public $template;

	/**
	 * Icon for the editor button. This is a class name from the Dashicons set.
	 *
	 * @since 2.0.0
	 * @var mixed
	 */
	public $icon = false;

	/**
	 * Priority order the field button should show inside the "Add Fields" tab.
	 *
	 * @since 2.0.0
	 * @var integer
	 */
	public $order = 20;

	/**
	 * Field group the field belongs to.
	 *
	 * @since 2.0.0
	 * @var string
	 */
	public $group = 'standard';

	/**
	 * Placeholder to hold default value(s) for some field types.
	 *
	 * @since 2.0.0
	 * @var mixed
	 */
	public $defaults;

	/**
	 * Current form ID in the admin builder.
	 *
	 * @since 2.0.0
	 * @var mixed, int or false
	 */
	public $form_id;

	/**
	 * Current form data in admin builder.
	 *
	 * @since 2.0.0
	 * @var mixed, int or false
	 */
	public $form_data;

	/**
	 * List of settings available for this field type.
	 *
	 * @var array
	 */
	public $settings;

	/**
	 * Register the field
	 *
	 * @since 2.0.0
	 */
	public function register() {
		// The form ID is to be accessed in the builder.
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

		// Add fields tab.
		add_filter( 'wpum_registered_field_types', array( $this, 'register_field_type' ), 15 );
	}

	/**
	 * Setup the editor settings for this field type.
	 *
	 * @return array
	 */
	public function get_editor_settings() {
		return [];
	}

	public function get_data_keys() {
		return [];
	}

	/**
	 * Setup the default settings for all field types.
	 *
	 * @return array
	 */
	private function get_default_editor_settings() {

		$settings = [
			'general' => [
				'field_title' => array(
					'type'        => 'input',
					'inputType'   => 'text',
					'label'       => esc_html__( 'Field title', 'wp-user-manager' ),
					'model'       => 'field_title',
					'required'    => true,
					'placeholder' => esc_html__( 'Enter a title for this field', 'wp-user-manager' ),
					'validator'   => [ 'string' ],
					'min'         => 1
				),
				'field_description' => array(
					'type'      => 'textArea',
					'inputType' => 'text',
					'label'     => esc_html__( 'Field description (optional)', 'wp-user-manager' ),
					'model'     => 'field_description',
					'rows'      => 3,
					'hint'      => esc_html__( 'This is the text that appears as a description within the forms. Leave blank if not needed.', 'wp-user-manager' )
				),
				'user_meta_key' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Unique meta key', 'wp-user-manager' ),
					'model'     => 'user_meta_key',
					'required'  => true,
					'hint'      => esc_html__( 'The key must be unique for each field and written in lowercase with an underscore ( _ ) separating words e.g country_list or job_title. This will be used to store information about your users into the database of your website.', 'wp-user-manager' ),
					'validator' => [ 'string' ],
					'min'       => 1
				),
				'placeholder' => array(
					'type'      => 'input',
					'inputType' => 'text',
					'label'     => esc_html__( 'Placeholder', 'wp-user-manager' ),
					'model'     => 'placeholder',
					'hint'      => esc_html__( 'This text will appear within the field when empty. Leave blank if not needed.', 'wp-user-manager' ),
				),
			],
			'validation' => [
				'required' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Set as required', 'wp-user-manager' ),
					'model'   => 'required',
					'default' => false,
					'hint'    => esc_html__( 'Enable this option so the field must be filled before the form can be processed.', 'wp-user-manager' ),
				)
			],
			'privacy' => [
				'visibility' => array(
					'type'    => 'radios',
					'label'   => esc_html__( 'Profile visibility', 'wp-user-manager' ),
					'model'   => 'visibility',
					'hint'    => esc_html__( 'Set the visibility of this field on users profiles.', 'wp-user-manager' ),
					'values' => [
						[ 'value' => 'public', 'name' => esc_html__( 'Publicly visible', 'wp-user-manager' ) ],
						[ 'value' => 'hidden', 'name' => esc_html__( 'Hidden', 'wp-user-manager' ) ]
					],
					'noneSelectedText'     => '',
					'hideNoneSelectedText' =>  true,
				)
			],
			'permissions' => [
				'editing' => array(
					'type'    => 'radios',
					'label'   => esc_html__( 'Profile editing', 'wp-user-manager' ),
					'model'   => 'editing',
					'hint'    => esc_html__( 'Set who can edit this field. Hidden fields will not be editable within the front-end account page.', 'wp-user-manager' ),
					'values' => [
						[ 'value' => 'public', 'name' => esc_html__( 'Publicly editable', 'wp-user-manager' ) ],
						[ 'value' => 'hidden', 'name' => esc_html__( 'Hidden (admins only)', 'wp-user-manager' ) ]
					],
				),
				'read_only' => array(
					'type'    => 'checkbox',
					'label'   => esc_html__( 'Set as read only', 'wp-user-manager' ),
					'model'   => 'read_only',
					'default' => false,
					'hint'    => esc_html__( 'Enable to prevent users from editing this field. Note: if the profile editing option is set to publicly editable, the field will still be visible within the account page but will not be customizable.', 'wp-user-manager' ),
				)
			]
		];

		return apply_filters( 'wpum_register_field_type_settings', $settings, $this->type, $this->type );

	}

	/**
	 * Register fields into an array that can be easily retrieved.
	 *
	 * @param array $fields
	 *
	 * @return array
	 */
	public function register_field_type( $fields ) {
		$settings =  array_merge_recursive( $this->get_default_editor_settings(), $this->get_editor_settings() );

		$fields[ $this->group ]['fields'][] = array(
			'order'    => $this->order,
			'name'     => $this->name,
			'type'     => $this->type,
			'icon'     => $this->icon,
			'settings' => apply_filters( 'wpum_register_field_type_settings', $settings, $this->type )
		);

		return $fields;
	}

	/**
	 * @return string
	 */
	public function template() {
		if ( ! empty( $this->template ) ) {
			return $this->template;
		}

		return $this->type;
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
	public function get_posted_field( $key, $field ) {
		// Allow custom sanitizers with standard text fields.
		if ( ! isset( $field['sanitizer'] ) ) {
			$field['sanitizer'] = null;
		}
		return isset( $_POST[ $key ] ) ? $this->sanitize_posted_field( $_POST[ $key ], $field['sanitizer'] ) : '';
	}

	/**
	 * Format the output onto the profiles for the text field.
	 *
	 * @param object $field
	 * @param mixed $value
	 * @return string
	 */
	function get_formatted_output( $field, $value ) {
		return esc_html( $value );
	}

}
