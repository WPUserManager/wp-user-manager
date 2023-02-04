<?php
/**
 * Handles the display of additional columns within the user list table.
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
 * Add new columns to the user list table.
 */
class WPUM_User_Table {

	/**
	 * Get things started.
	 */
	public function __construct() {
		add_filter( 'manage_users_columns', array( $this, 'add_user_id_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'show_user_id' ), 10, 3 );
		add_action( 'admin_head', array( $this, 'hide_change_role_field' ) );
		add_action( 'load-users.php', array( $this, 'load_users' ) );
		add_action( 'load-users.php', array( $this, 'handle_users_role_bulk_add' ) );
		add_action( 'load-users.php', array( $this, 'handle_users_role_bulk_remove' ) );
	}

	/**
	 * Change role field
	 */
	public function hide_change_role_field() {
		?>
		<style type="text/css">
			label[for="new_role"], #new_role, #changeit,
			label[for="new_role2"], #new_role2, #changeit2 { display: none !important; }
		</style>
		<?php
	}

	/**
	 * @param string $which
	 */
	public function bulk_fields_dropdown( $which ) {
		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		wp_nonce_field( 'wpum-bulk-users', 'wpum-bulk-users-nonce' );
		?>

		<label class="screen-reader-text" for="<?php echo esc_attr( "wpum-add-role-{$which}" ); ?>">
			<?php esc_html_e( 'Add role&hellip;', 'wp-user-manager' ); ?>
		</label>
		<select name="<?php echo esc_attr( "wpum-add-role-{$which}" ); ?>"
				id="<?php echo esc_attr( "wpum-add-role-{$which}" ); ?>" style="display: inline-block; float: none;">
			<option value=""><?php esc_html_e( 'Add role&hellip;', 'wp-user-manager' ); ?></option>
			<?php wp_dropdown_roles(); ?>
		</select>

		<?php submit_button( esc_html__( 'Add', 'wp-user-manager' ), 'secondary', esc_attr( "wpum-add-role-submit-{$which}" ), false ); ?>

		<label class="screen-reader-text" for="<?php echo esc_attr( "wpum-remove-role-{$which}" ); ?>">
			<?php esc_html_e( 'Remove role&hellip;', 'wp-user-manager' ); ?>
		</label>

		<select name="<?php echo esc_attr( "wpum-remove-role-{$which}" ); ?>"
				id="<?php echo esc_attr( "wpum-remove-role-{$which}" ); ?>" style="display: inline-block; float: none;">
			<option value=""><?php esc_html_e( 'Remove role&hellip;', 'wp-user-manager' ); ?></option>
			<?php wp_dropdown_roles(); ?>
		</select>

		<?php
		submit_button( esc_html__( 'Remove', 'wp-user-manager' ), 'secondary', esc_attr( "wpum-remove-role-submit-{$which}" ), false );
	}

	/**
	 * Load users
	 */
	public function load_users() {
		add_action( 'restrict_manage_users', array( $this, 'bulk_fields_dropdown' ), 5 );

		$update = filter_input( INPUT_GET, 'update' );
		$name   = filter_input( INPUT_GET, 'name' );

		if ( ! $update || ! $name ) {
			return;
		}

		$action = sanitize_key( $update );
		$role   = sanitize_key( $name );
		$count  = filter_input( INPUT_GET, 'count', FILTER_VALIDATE_INT );

		if ( 'wpum-role-added' === $action ) {
			WPUM()->notices->register_notice( 'wpum_role_added', 'success', sprintf( '<b>%s</b> role added to <b>%d</b> %s.', ucwords( $role ), $count, $count > 1 ? 'users' : 'user' ), 'wp-user-manager' );
		} elseif ( 'wpum-role-removed' === $action ) {
			WPUM()->notices->register_notice( 'wpum_role_removed', 'success', sprintf( '<b>%s</b> role removed from <b>%d</b> %s.', ucwords( $role ), $count, $count > 1 ? 'users' : 'user' ), 'wp-user-manager' );
		} elseif ( 'wpum-error-remove-admin' === $action ) {
			WPUM()->notices->register_notice( 'wpum_admin_error', 'error', sprintf( 'You cannot remove the <b>%s</b> role from your account.', ucwords( $role ) ), 'wp-user-manager' );
		}
	}

	/**
	 * Handle bulk adding of roles
	 */
	public function handle_users_role_bulk_add() {
		if ( empty( $_REQUEST['users'] ) ) {
			return;
		}

		if ( empty( $_REQUEST['wpum-add-role-top'] ) && empty( $_REQUEST['wpum-add-role-bottom'] ) ) {
			return;
		}

		check_admin_referer( 'wpum-bulk-users', 'wpum-bulk-users-nonce' );

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['wpum-add-role-top'] ) && ! empty( $_REQUEST['wpum-add-role-submit-top'] ) ) {
			$role = sanitize_text_field( wp_unslash( $_REQUEST['wpum-add-role-top'] ) );
		} elseif ( ! empty( $_REQUEST['wpum-add-role-bottom'] ) && ! empty( $_REQUEST['wpum-add-role-submit-bottom'] ) ) {
			$role = sanitize_text_field( wp_unslash( $_REQUEST['wpum-add-role-bottom'] ) );
		}

		$m_role = wpum_get_role( $role );
		$roles  = array_column( wpum_get_roles( false, true ), 'value' );

		if ( empty( $role ) || ! in_array( $role, $roles, true ) ) {
			return;
		}

		$count = 0;

		$users = filter_input( INPUT_GET, 'users', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		if ( empty( $users ) ) {
			$users = array();
		}

		foreach ( $users as $user_id ) {

			$user_id = absint( $user_id );
			if ( is_multisite() && ! is_user_member_of_blog( $user_id ) ) {

				wp_die( sprintf( '<p>%s</p>', esc_html__( 'One of the selected users is not a member of this site.', 'wp-user-manager' ) ), 403 );
			}

			if ( ! current_user_can( 'promote_user', $user_id ) ) {
				continue;
			}

			$user = new \WP_User( $user_id );

			if ( ! in_array( $role, $user->roles, true ) ) {
				$user->add_role( $role );
				$count ++;
			}
		}
		wp_safe_redirect( add_query_arg( array(
			'update' => 'wpum-role-added',
			'name'   => $m_role->label,
			'count'  => $count,
		), 'users.php' ) );

		exit;
	}

	/**
	 * Handle removing roles in bulk
	 */
	public function handle_users_role_bulk_remove() {
		if ( empty( $_REQUEST['users'] ) ) {
			return;
		}

		if ( empty( $_REQUEST['wpum-remove-role-top'] ) && empty( $_REQUEST['wpum-remove-role-bottom'] ) ) {
			return;
		}

		check_admin_referer( 'wpum-bulk-users', 'wpum-bulk-users-nonce' );

		if ( ! current_user_can( 'promote_users' ) ) {
			return;
		}

		if ( ! empty( $_REQUEST['wpum-remove-role-top'] ) && ! empty( $_REQUEST['wpum-remove-role-submit-top'] ) ) {
			$role = sanitize_text_field( wp_unslash( $_REQUEST['wpum-remove-role-top'] ) );
		} elseif ( ! empty( $_REQUEST['wpum-remove-role-bottom'] ) && ! empty( $_REQUEST['wpum-remove-role-submit-bottom'] ) ) {
			$role = sanitize_text_field( wp_unslash( $_REQUEST['wpum-remove-role-bottom'] ) );
		}

		$roles = array_column( wpum_get_roles(), 'value' );

		if ( empty( $role ) || ! in_array( $role, $roles, true ) ) {
			return;
		}

		$current_user = wp_get_current_user();
		$m_role       = wpum_get_role( $role );
		$update       = 'wpum-role-removed';

		$count = 0;
		$users = filter_input( INPUT_GET, 'users', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY );
		if ( empty( $users ) ) {
			$users = array();
		}

		foreach ( $users as $user_id ) {

			$user_id = absint( $user_id );

			if ( is_multisite() && ! is_user_member_of_blog( $user_id ) ) {

				wp_die( sprintf( '<p>%s</p>', esc_html__( 'One of the selected users is not a member of this site.', 'wp-user-manager' ) ), 403 );
			}

			if ( ! current_user_can( 'promote_user', $user_id ) ) {
				continue;
			}

			$is_current_user    = $user_id === $current_user->ID;
			$role_can_promote   = in_array( 'promote_users', $m_role->granted_caps, true );
			$can_manage_network = is_multisite() && current_user_can( 'manage_network_users' );

			if ( $is_current_user && $role_can_promote && ! $can_manage_network ) {
				$can_remove = false;

				foreach ( $current_user->roles as $_r ) {

					if ( $role !== $_r && in_array( 'promote_users', wpum_get_role( $_r )->granted_caps, true ) ) {

						$can_remove = true;
						break;
					}
				}

				if ( ! $can_remove ) {
					$update = 'wpum-error-remove-admin';
					continue;
				}
			}

			$user = new \WP_User( $user_id );

			if ( in_array( $role, $user->roles, true ) ) {
				$user->remove_role( $role );
				$count ++;
			}
		}
		wp_safe_redirect( add_query_arg( array(
			'update' => $update,
			'name'   => $m_role->label,
			'count'  => $count,
		), 'users.php' ) );

		exit;
	}

	/**
	 * Add the user id column.
	 *
	 * @param array $columns
	 * @return array
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
	 * @param int    $user_id
	 * @return mixed
	 */
	public function show_user_id( $value, $column_name, $user_id ) {
		if ( 'user_id' === $column_name ) {
			return $user_id;
		}

		return $value;
	}

}

new WPUM_User_Table();
