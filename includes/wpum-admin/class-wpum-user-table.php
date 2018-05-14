<?php
/**
 * Handles the display of additional columns within the user list table.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add new columns to the user list table.
 */
class WPUM_User_Table {

	/**
	 * Get things started.
	 */
	public function __construct() {

		add_filter( 'manage_users_columns', [ $this, 'add_user_id_column' ] );
		add_action( 'manage_users_custom_column',  [ $this, 'show_user_id' ], 10, 3 );

	}

	/**
	 * Add the user id column.
	 *
	 * @param array $columns
	 * @return void
	 */
	public function add_user_id_column( $columns ) {

		$columns['user_id'] = __( 'ID', 'wp-user-manager' );
		return $columns;

	}

	/**
	 * Show the user id within the user id column.
	 *
	 * @param string $value
	 * @param string $column_name
	 * @param int $user_id
	 * @return void
	 */
	public function show_user_id( $value, $column_name, $user_id ) {
		if ( 'user_id' == $column_name ) {
			return $user_id;
		}
		return $value;
	}

}

new WPUM_User_Table;
