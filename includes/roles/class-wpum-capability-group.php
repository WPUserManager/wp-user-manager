<?php
/**
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * WPUM_Capability_Group
 */
final class WPUM_Capability_Group {

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
	public $icon = 'dashicons-admin-generic';

	/**
	 * @var array
	 */
	public $caps = array();

	/**
	 * @var int
	 */
	public $priority = 10;

	/**
	 * @var bool
	 */
	public $diff_added = false;

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->name;
	}

	/**
	 * WPUM_Capability_Group constructor.
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

		$this->name = sanitize_key( $name );

		$registered_caps = array_keys( wp_list_filter( wpum_get_caps(), array( 'group' => $this->name ) ) );

		$this->caps = array_unique( array_merge( $this->caps, $registered_caps ) );

		$this->caps = wpum_remove_hidden_caps( $this->caps );
	}
}
