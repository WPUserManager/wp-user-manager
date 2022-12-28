<?php
/**
 * WPUM_Role
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * WPUM_Role
 */
class WPUM_Role {

	/**
	 * @var string
	 */
	public $name = '';

	/**
	 * @var string
	 */
	public $label = '';

	/**
	 * @var string
	 */
	public $group = '';

	/**
	 * @var bool
	 */
	public $has_caps = false;

	/**
	 * @var int
	 */
	public $granted_cap_count = 0;

	/**
	 * @var int
	 */
	public $denied_cap_count = 0;

	/**
	 * @var array
	 */
	public $caps = array();

	/**
	 * @var array
	 */
	public $granted_caps = array();

	/**
	 * @var array
	 */
	public $denied_caps = array();

	/**
	 * Return the role string in attempts to use the object as a string.
	 *
	 * @since  2.0.0
	 * @access public
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	/**
	 * Creates a new role object.
	 *
	 * @param string $name
	 * @param array  $args
	 */
	public function __construct( $name, $args = array() ) {
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {

			if ( isset( $args[ $key ] ) ) {
				$this->$key = $args[ $key ];
			}
		}

		$this->name = wpum_sanitize_role( $name );
		if ( $this->caps ) {

			// Validate cap values as booleans in case they are stored as strings.
			$this->caps = array_map( function ( $val ) {
				return filter_var( $val, FILTER_VALIDATE_BOOLEAN );
			}, $this->caps );

			// Get granted and denied caps.
			$this->granted_caps = array_keys( $this->caps, true, true );
			$this->denied_caps  = array_keys( $this->caps, false, true );

			// Remove user levels from granted/denied caps.
			$this->granted_caps = wpum_remove_old_levels( $this->granted_caps );
			$this->denied_caps  = wpum_remove_old_levels( $this->denied_caps );

			// Remove hidden caps from granted/denied caps.
			$this->granted_caps = wpum_remove_hidden_caps( $this->granted_caps );
			$this->denied_caps  = wpum_remove_hidden_caps( $this->denied_caps );

			// Set the cap count.
			$this->granted_cap_count = count( $this->granted_caps );
			$this->denied_cap_count  = count( $this->denied_caps );

			// Check if we have caps.
			$this->has_caps = 0 < $this->granted_cap_count;
		}
	}

	/**
	 * Magic method for getting media object properties.  Let's keep from failing if a theme
	 * author attempts to access a property that doesn't exist.
	 *
	 * @since  2.0.2
	 * @access public
	 * @param  string $property
	 * @return mixed
	 */
	public function get( $property ) {
		if ( 'label' === $property ) {
			return wpum_translate_role( $this->name );
		}

		return isset( $this->$property ) ? $this->$property : false;
	}
}
