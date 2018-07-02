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

		if ( isset( $_GET['directory-search'] ) && ! empty( $_GET['directory-search'] ) && isset( $_GET['directory-id'] ) && ! empty( $_GET['directory-id'] ) && ! is_admin() ) {

			global $wpdb;

			$terms          = $this->get_search_terms();
			$search_with_or = in_array( 'or', $terms );
			$directory_id   = absint( $_GET['directory-id'] );

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

			// Exclude users from the directory that have been specified within the settings.
			if( is_array( $user_ids ) ) {
				// Retrieve any excluded user ids from the submitted directory.
				$excluded_users = carbon_get_post_meta( $directory_id, 'directory_excluded_users' );

				if( ! empty( $excluded_users ) ) {
					$excluded_users = trim( str_replace(' ','', $excluded_users ) );
					$excluded_users = explode(',', $excluded_users );
				}

				// Exclude users from the query.
				if( is_array( $excluded_users ) && ! empty( $excluded_users ) ) {
					foreach ( $excluded_users as $excluded_user_id ) {
						if ( ( $key = array_search( $excluded_user_id, $user_ids ) ) !== false ) {
							unset( $user_ids[ $key ] );
						}
					}
				}
			}

			// Keep assigned roles within the directory during the search query.
			$assigned_roles = carbon_get_post_meta( $directory_id, 'directory_assigned_roles' );

			if( is_array( $assigned_roles ) && ! empty( $assigned_roles ) ) {
				foreach ( $user_ids as $user_id ) {
					$user = get_user_by( 'id', $user_id );
					if ( ! in_array( $assigned_roles, (array) $user->roles ) ) {
						if ( ( $key = array_search( $user_id, $user_ids ) ) !== false ) {
							unset( $user_ids[ $key ] );
						}
					}
				}
			}

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
