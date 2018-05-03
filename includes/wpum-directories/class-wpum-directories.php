<?php
/**
 * Handles extra options for the user directories.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * The Directories class.
 */
class WPUM_Directories {

	/**
	 * Get things started.
	 */
	public function __construct() {
		$this->hooks();
	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'pre_user_query', [ $this, 'pre_user_query' ], 100 );
	}

	/**
	 * Modify the search query on the frontend by grabbing the custom search fields defined into the db.
	 *
	 * @param object $user_query
	 * @return void
	 */
	public function pre_user_query( $user_query ) {

		if( isset( $_GET['directory-search'] ) && ! empty( $_GET['directory-search'] ) && ! is_admin() ) {

			global $wpdb;

			$terms          = $this->get_search_terms();
			$search_with_or = in_array( 'or', $terms );

			if ( $search_with_or ) {
				$terms = array_diff( $terms, array( 'or', 'and' ) );
				$terms = array_values( $terms );
			}

			$values = array();

			foreach ( $terms as $term ) {
				for ( $i = 0; $i < 6; $i++ ) {
					$values[] = "%{$term}%";
				}
			}

			$values[] = ( $search_with_or !== false ? 1 : count( $terms ) );

			$user_ids = $wpdb->get_col( $sql = $wpdb->prepare( "
				SELECT user_id
				FROM (" . implode( 'UNION ALL', array_fill( 0, count( $terms ), "
					SELECT DISTINCT u.ID AS user_id
					FROM {$wpdb->users} u
					INNER JOIN {$wpdb->usermeta} um
					ON um.user_id = u.ID
					INNER JOIN {$wpdb->wpum_search_fields} mk
					ON mk.meta_key = um.meta_key
					WHERE LOWER(um.meta_value) LIKE %s
					OR LOWER(u.user_login) LIKE %s
					OR LOWER(u.user_nicename) LIKE %s
					OR LOWER(u.user_email) LIKE %s
					OR LOWER(u.user_url) LIKE %s
					OR LOWER(u.display_name) LIKE %s
				" ) ) . ") AS user_search_union
				GROUP BY user_id
				HAVING COUNT(*) >= %d;
			", $values ) );

			if ( is_array( $user_ids ) && count( $user_ids ) ) {
				// Combine the IDs into a comma separated list.
				$id_string = implode( ',', $user_ids );

				// Build the SQL we are adding to the query
				$extra_sql = " OR ID IN ({$id_string})";

				$add_after    = 'WHERE ';
				$add_position = strpos( $user_query->query_where, $add_after ) + strlen( $add_after );

				// Add the query to the end, after wrapping the rest in parenthesis
				$user_query->query_where = substr( $user_query->query_where, 0, $add_position ) . '(' . substr( $user_query->query_where, $add_position ) . ')' . $extra_sql;
			}

		}

	}

	/**
	 * Get the defined list of search terms into an array.
	 *
	 * @return array
	 */
	private function get_search_terms() {

		$terms = sanitize_text_field( esc_attr( trim( $_GET['directory-search'] ) ) );

		if ( empty( $terms ) ) {
			return array();
		}

		$terms = explode( ' ', $terms );

		foreach ( $terms as $key => $term ) {
			if ( empty( $term ) ) {
				unset( $terms[ $key ] );
			}
		}

		$terms = array_values( $terms );

		return $terms;

	}

}

new WPUM_Directories;
