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
	 * Icon for the editor button, eg "fa-list".
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
	 * Primary class constructor.
	 *
	 * @since 2.0.0
	 *
	 * @param bool $init
	 */
	public function __construct() {

		// The form ID is to be accessed in the builder.
		$this->form_id = isset( $_GET['form_id'] ) ? absint( $_GET['form_id'] ) : false;

		// Bootstrap.
		$this->init();

		// Add fields tab.
		add_filter( 'wpum_registered_field_types', array( $this, 'register_field' ), 15 );

	}

	/**
	 * All systems go. Used by subclasses.
	 *
	 * @since 2.0.0
	 */
	public function init() {}

	/**
	 * Register fields into an array that can be easily retrieved.
	 *
	 * @param array $fields
	 * @return void
	 */
	public function register_field( $fields ) {

		$fields[ $this->group ]['fields'][] = array(
			'order' => $this->order,
			'name'  => $this->name,
			'type'  => $this->type,
			'icon'  => $this->icon,
		);

		return $fields;

	}

}
