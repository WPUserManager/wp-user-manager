<?php
/**
 * WPUM_Collection
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

/**
 * WPUM_Collection
 */
class WPUM_Collection {

	/**
	 * @var array
	 */
	private static $instances = array();

	/**
	 * @var array
	 */
	protected $items = array();

	/**
	 * Construct
	 */
	public function __construct() {
	}

	/**
	 * Clone
	 */
	public function __clone() {
	}

	/**
	 * Wakeup
	 */
	public function __wakeup() {
	}

	/**
	 * Register an item.
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function register( $name, $value ) {
		if ( ! $this->exists( $name ) ) {
			$this->items[ $name ] = $value;
		}
	}

	/**
	 * Unregisters an item from the collection.
	 *
	 * @param string $name
	 *
	 * @return void
	 */
	public function unregister( $name ) {
		if ( $this->exists( $name ) ) {
			unset( $this->items[ $name ] );
		}
	}

	/**
	 * Checks if an item exists in the collection
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function exists( $name ) {
		return isset( $this->items[ $name ] );
	}

	/**
	 * Returns an item from the collection
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get( $name ) {
		return $this->exists( $name ) ? $this->items[ $name ] : false;
	}

	/**
	 * Returns the entire collection.
	 *
	 * @return array
	 */
	public function get_items() {
		return $this->items;
	}

	/**
	 * Returns the instance.
	 *
	 * @param string $name
	 *
	 * @return WPUM_Collection
	 */
	final public static function get_instance( $name = '' ) {
		if ( ! isset( self::$instances[ $name ] ) ) {
			self::$instances[ $name ] = new static();
		}

		return self::$instances[ $name ];
	}
}
