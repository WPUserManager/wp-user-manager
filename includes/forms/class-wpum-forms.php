<?php
/**
 * Handles all the setup of the forms.
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
 * Base class for all WPUM forms.
 */
class WPUM_Forms {

	/**
	 * The single instance of the class.
	 *
	 * @var WPUM_Forms
	 */
	private static $instance = null;

	/**
	 * Allows for accessing single instance of class. Class should only be constructed once per call.
	 *
	 * @static
	 * @return WPUM_Forms Main instance.
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
		add_action( 'init', array( $this, 'load_posted_form' ) );
	}

	/**
	 * If a form was posted, load its class so that it can be processed before display.
	 *
	 * @param null $form
	 *
	 * @return false|WPUM_Form
	 */
	public function load_posted_form( $form = null ) {
		if ( empty( $form ) ) {
			$form = filter_input( INPUT_POST, 'wpum_form' );
		}

		if ( ! empty( $form ) ) {
			return $this->load_form_class( sanitize_title( $form ) );
		}

		return false;
	}

	/**
	 * Load a form's class
	 *
	 * @param  string $form_name
	 * @return string class name on success, false on failure
	 */
	private function load_form_class( $form_name ) {
		if ( ! class_exists( 'WPUM_Form' ) ) {
			include WPUM_PLUGIN_DIR . 'includes/abstracts/class-wpum-form.php';
		}
		// Now try to load the form_name
		$form_class = apply_filters( 'wpum_load_form_class', 'WPUM_Form_' . str_replace( '-', '_', $form_name ), $form_name );
		$form_file  = WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-form-' . $form_name . '.php';
		$form_file  = apply_filters( 'wpum_load_form_path', $form_file, $form_name );

		if ( class_exists( $form_class ) ) {
			return call_user_func( array( $form_class, 'instance' ) );
		}
		if ( ! file_exists( $form_file ) ) {
			return false;
		}
		if ( ! class_exists( $form_class ) ) {
			include $form_file;
		}
		// Init the form.
		return call_user_func( array( $form_class, 'instance' ) );
	}

	/**
	 * Returns the form content.
	 *
	 * @param string $form_name
	 * @param array  $atts Optional passed attributes
	 * @return string|null
	 */
	public function get_form( $form_name, $atts = array() ) {
		$form = $this->load_form_class( $form_name );
		if ( $form ) {
			ob_start();
			if ( isset( $atts['group_id'] ) ) {
				$form->__set( 'fields_group_id', $atts['group_id'] );
			}
			$form->output( $atts );
			return ob_get_clean();
		}
	}
}
