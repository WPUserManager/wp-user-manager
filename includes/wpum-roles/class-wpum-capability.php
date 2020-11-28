<?php
/**
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

class WPUM_Capability {

	public $name = '';

	public $label = '';

	public $group = '';

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	public function __construct( $name, $args = array() ) {
		foreach ( array_keys( get_object_vars( $this ) ) as $key ) {

			if ( isset( $args[ $key ] ) ) {
				$this->$key = $args[ $key ];
			}
		}

		$this->name = sanitize_key( $name );
	}
}
