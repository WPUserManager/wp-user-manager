<?php
/**
 * Actions meant to be triggered everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delete cached list of pages when a page is updated or created.
 * This is needed to refresh the list of available pages for the options panel.
 *
 * @param string $post_id
 * @return void
 */
function wpum_delete_pages_transient( $post_id ) {

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	delete_transient( 'wpum_get_pages' );

}
add_action( 'save_post_page', 'wpum_delete_pages_transient' );

/**
 * Delete cached list of pages when a page is deleted.
 *
 * @param int     $post_id
 * @param WP_Post $post
 *
 * @return void
 */
function wpum_delete_pages_transient_on_delete( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || 'page' !== $post->post_type ) {
		return;
	}

	delete_transient( 'wpum_get_pages' );
}

add_action( 'delete_post', 'wpum_delete_pages_transient_on_delete', 99, 2 );

/**
 * Add WPUM specific admin bar links.
 *
 * @param object $wp_admin_bar
 * @return void
 */
function wpum_admin_bar_menu( $wp_admin_bar ) {

	if ( ! current_user_can( apply_filters( 'wpum_admin_pages_capability', 'manage_options' ) ) ) {
		return;
	}

	$args = array(
		'id'    => 'wpum_node',
		'href'  => admin_url( 'users.php' ),
		'title' => '<span class="ab-icon dashicons dashicons-admin-users" style="margin-top:2px"></span>' . esc_html__( ' Users', 'wp-user-manager' ),
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_emails',
		'href'   => admin_url( 'users.php?page=wpum-emails' ),
		'title'  => esc_html__( 'Emails', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_custom_fields',
		'href'   => admin_url( 'users.php?page=wpum-custom-fields' ),
		'title'  => esc_html__( 'Custom fields', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_registration_forms',
		'href'   => admin_url( 'users.php?page=wpum-registration-forms' ),
		'title'  => esc_html__( 'Registration Forms', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

	$args = array(
		'id'     => 'wpum_settings',
		'href'   => admin_url( 'users.php?page=wpum-settings' ),
		'title'  => esc_html__( 'Settings', 'wp-user-manager' ),
		'parent' => 'wpum_node',
	);
	$wp_admin_bar->add_node( $args );

}
add_action( 'admin_bar_menu', 'wpum_admin_bar_menu', 100 );

/**
 * Hide admin bar from the frontend based on the selected roles.
 *
 * @return void
 */
function wpum_remove_admin_bar() {
	$excluded_roles = wpum_get_option( 'adminbar_roles' );

	if ( empty( $excluded_roles ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( is_admin() ) {
		return;
	}

	$user = wp_get_current_user();

	if ( empty( $user->roles ) || ! is_array( $user->roles ) ) {
		return;
	}

	foreach ( $user->roles as $user_role ) {
		if ( in_array( $user_role, $excluded_roles, true ) ) {
			show_admin_bar( false );

			return;
		}
	}
}
add_action( 'after_setup_theme', 'wpum_remove_admin_bar' );

/**
 * Handle redirecting users away from the wp-admin unless in allowed role.
 */
function wpum_restrict_wp_admin_dashboard_access() {
	if ( ! is_admin() ) {
		return;
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	if ( current_user_can( 'administrator' ) ) {
		return;
	}

	$excluded_roles = wpum_get_option( 'wp_admin_roles' );
	if ( empty( $excluded_roles ) ) {
		return;
	}

	$user = wp_get_current_user();
	if ( ! in_array( $user->roles[0], $excluded_roles, true ) ) {
		return;
	}

	$redirect = apply_filters( 'wpum_restrict_wp_admin_dashboard_access_redirect', home_url() );

	nocache_headers();
	wp_safe_redirect( $redirect );
	exit;
}

add_action( 'init', 'wpum_restrict_wp_admin_dashboard_access' );

/**
 * Restrict access to the wp-login.php registration page
 * and redirect to the WPUM registration page.
 *
 * @return void
 */
function wpum_restrict_wp_registration() {

	$registration_redirect = wpum_get_option( 'wp_login_signup_redirect' );

	if ( $registration_redirect ) {
		wp_safe_redirect( esc_url( get_permalink( $registration_redirect[0] ) ) );
		exit;
	}

}
add_action( 'login_form_register', 'wpum_restrict_wp_registration' );

/**
 * Restrict access to wp-login.php?action=lostpassword
 *
 * @return void
 */
function wpum_restrict_wp_lostpassword() {

	$password_redirect = wpum_get_option( 'wp_login_password_redirect' );

	if ( $password_redirect ) {
		wp_safe_redirect( esc_url( get_permalink( $password_redirect[0] ) ) );
		exit;
	}

}
add_action( 'login_form_lostpassword', 'wpum_restrict_wp_lostpassword' );

/**
 * Restrict access to WordPress admin profile.
 *
 * @return void
 */
function wpum_restrict_wp_profile() {

	$profile_redirect = wpum_get_option( 'backend_profile_redirect' );

	if ( ! current_user_can( 'administrator' ) && IS_PROFILE_PAGE && $profile_redirect ) {
		wp_safe_redirect( esc_url( get_permalink( $profile_redirect[0] ) ) );
		exit;
	}

}
add_action( 'load-profile.php', 'wpum_restrict_wp_profile' );

/**
 * Restrict access to the account page only to logged in users.
 * After login, redirect visitors back to the account page.
 *
 * @return void
 */
function wpum_restrict_account_page() {

	$account_page = wpum_get_core_page_id( 'account' );
	$login_page   = wpum_get_core_page_id( 'login' );

	if ( $account_page && is_page( $account_page ) && ! is_user_logged_in() && $login_page ) {

		$redirect = get_permalink( $login_page );
		$redirect = add_query_arg(
			array(
				'redirect_to' => apply_filters( 'wpum_login_redirect_to_url', get_permalink() ),
			),
			$redirect
		);

		wp_safe_redirect( $redirect );
		exit;

	}

}
add_action( 'template_redirect', 'wpum_restrict_account_page' );

/**
 * Display the appropriate content for the account page given the currently active tab.
 *
 * @return void
 */
function wpum_display_account_page_content() {

	$active_tab = get_query_var( 'tab' );
	$tabs       = wpum_get_account_page_tabs();

	if ( empty( $active_tab ) ) {
		$active_tab = key( $tabs );
	}

	if ( in_array( $active_tab, array( 'settings', 'password', 'privacy' ), true ) ) {
		if ( 'settings' === $active_tab ) {
			$active_tab = 'profile';
		}
		echo WPUM()->forms->get_form( $active_tab ); // phpcs:ignore
	} else {
		do_action( 'wpum_account_page_content_' . $active_tab );
	}

}
add_action( 'wpum_account_page_content', 'wpum_display_account_page_content' );

/**
 * Make nickname unique.
 *
 * @param int $user_id
 * @return void
 */
function wpum_check_display_name( $user_id ) {

	global $wpdb;

	$display_name = filter_input( INPUT_POST, 'display_name' );
	$nickname     = filter_input( INPUT_POST, 'nickname' );

	// Getting user data and user meta data.
	$err['display'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users WHERE display_name = %s AND ID <> %d", $display_name, $user_id ) ); // phpcs:ignore
	$err['nick']    = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM $wpdb->users as users, $wpdb->usermeta as meta WHERE users.ID = meta.user_id AND meta.meta_key = 'nickname' AND meta.meta_value = %s AND users.ID <> %d", $nickname, $user_id ) );  // phpcs:ignore

	foreach ( $err as $key => $e ) {
		if ( $e >= 1 ) {
			add_action( 'user_profile_update_errors', "wpum_check_{$key}_field", 10, 3 );
		}
	}
}
add_action( 'personal_options_update', 'wpum_check_display_name' );
add_action( 'edit_user_profile_update', 'wpum_check_display_name' );

/**
 * Trigger the unique error for the display field.
 *
 * @param WP_Error $errors
 * @param bool     $update
 * @param stdClass $user
 *
 * @return void
 */
function wpum_check_display_field( $errors, $update, $user ) {
	$errors->add( 'display_name_error', esc_html__( 'This display name is already in use by someone else. Display names must be unique.', 'wp-user-manager' ) );
}

/**
 * Trigger the unique error for the nickname field.
 *
 * @param WP_Error $errors
 * @param bool     $update
 * @param stdClass $user
 *
 * @return void
 */
function wpum_check_nick_field( $errors, $update, $user ) {
	$errors->add( 'display_nick_error', esc_html__( 'This nickname is already in use by someone else. Nicknames must be unique.', 'wp-user-manager' ) );
}

/**
 * Add a "view profile" link to the admin user table.
 *
 * @param  array  $actions     list of actions
 * @param  object $user_object user details
 * @return array              list of actions
 */
function wpum_admin_user_action_link( $actions, $user_object ) {
	if ( wpum_get_core_page_id( 'profile' ) ) {
		$actions['view_profile'] = '<a href="' . esc_url( wpum_get_profile_url( $user_object ) ) . '" target="_blank">' . esc_html__( 'View Profile', 'wp-user-manager' ) . '</a>';
	}
	return $actions;
}
add_filter( 'user_row_actions', 'wpum_admin_user_action_link', 10, 2 );

/**
 * Complete setup of the plugin once first loaded.
 *
 * @return void
 */
function wpum_complete_setup() {
	if ( ! get_option( 'wpum_setup_is_complete' ) && ! get_option( 'wpum_version_upgraded_from' ) ) {

		wpum_install_default_field_group();

		$fields = wpum_install_fields();

		wpum_install_cover_image_field();

		wpum_setup_default_custom_search_fields();

		wpum_install_registration_form( $fields );

		update_option( 'wpum_setup_is_complete', true );

	}
}

/**
 * Prevent access to wp-login.php
 *
 * @return void
 */
function wpum_prevent_wp_login() {
	global $pagenow;

	$action        = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
	$wpum_override = filter_input( INPUT_GET, 'wpum_override' );

	if ( $pagenow && 'wp-login.php' === $pagenow && ! $wpum_override && ( ! $action || ( ! in_array( $action, array( 'logout', 'lostpassword', 'rp', 'resetpass', 'postpass' ), true ) ) ) ) {
		$page = wp_login_url();
		wp_safe_redirect( $page );
		exit();
	}
}

if ( wpum_get_option( 'lock_wplogin' ) ) {
	add_action( 'init', 'wpum_prevent_wp_login' );
}

/**
 * Prevent access to site unless logged in
 *
 * @return void
 */
function wpum_prevent_entire_site() {
	if ( wp_doing_cron() || wp_doing_ajax() ) {
		return;
	}

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	global $pagenow;

	$login_page      = wp_login_url();
	$wp_login_locked = wpum_get_option( 'lock_wplogin' );
	$is_wp_login     = $pagenow && 'wp-login.php' === $pagenow;

	$url_part = filter_input( INPUT_SERVER, 'REQUEST_URI' );

	if ( empty( $url_part ) ) {
		$url_part = '';
	}

	$url = home_url( $url_part );

	if ( isset( $_SERVER['REQUEST_URI'] ) && $url === $login_page || ( $is_wp_login && ( ! empty( $_GET['wpum_override'] ) || ! $wp_login_locked ) ) ) { // phpcs:ignore
		return;
	}

	if ( isset( $_POST['wp-submit'] ) && isset( $_POST['log'] ) ) { // phpcs:ignore
		return;
	}

	$password_reset_page_id = wpum_get_core_page_id( 'password' );
	if ( ! empty( $password_reset_page_id ) ) {
		$password_reset_page = get_permalink( $password_reset_page_id );
		if ( 0 === strpos( $url, $password_reset_page ) ) {
			return;
		}
	}

	if ( wpum_get_option( 'lock_complete_site_allow_register' ) ) {
		$registration_pages   = array();
		$registration_pages[] = get_permalink( wpum_get_core_page_id( 'register' ) );

		foreach ( apply_filters( 'wpum_registration_pages', $registration_pages ) as $registration_page ) {
			if ( $url === $registration_page ) {
				return;
			}
		}
	}

	foreach ( apply_filters( 'wpum_prevent_entire_site_access_allowed_urls', array() ) as $allowed_url ) {
		if ( $url === $allowed_url ) {
			return;
		}
	}

	if ( ! apply_filters( 'wpum_prevent_entire_site_access', true ) ) {
		return;
	}

	wp_safe_redirect( $login_page );
	exit();
}

if ( wpum_get_option( 'lock_complete_site' ) ) {
	add_action( 'init', 'wpum_prevent_entire_site', 9 );
}

/**
 * Finish data installation after the whole plugin has booted.
 *
 * @return void
 */
function wpum_finish_db_setup_after_plugin_init() {

	$upgrade = get_option( 'wpum_version_upgraded_from' );
	if ( ! $upgrade ) {
		wpum_complete_setup();
	}

}
add_action( 'after_wpum_init', 'wpum_finish_db_setup_after_plugin_init' );

/**
 * Register user profile privacy fields
 */
function wpum_register_profile_privacy_fields() {
	global $pagenow;

	$roles = array();

	foreach ( wpum_get_roles( true, true ) as $role ) {
		$roles[ $role['value'] ] = $role['label'];
	}

	$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );

	$user_id = filter_input( INPUT_GET, 'user_id', FILTER_VALIDATE_INT );

	$profileuser    = isset( $user_id ) ? get_user_by( 'id', $user_id ) : false;
	$existing_roles = ( $profileuser ) ? $profileuser->roles : array();

	$fields = array(
		Field::make( 'checkbox', 'hide_profile_guests', esc_html__( 'Hide profile from guests', 'wp-user-manager' ) )
			->set_help_text( esc_html__( 'Hide this profile from guests. Overrides the global profile options.', 'wp-user-manager' ) ),
		Field::make( 'checkbox', 'hide_profile_members', esc_html__( 'Hide profile from members', 'wp-user-manager' ) )
			->set_help_text( esc_html__( 'Hide this profile from members. Overrides the global profile options.', 'wp-user-manager' ) ),
	);

	if ( $allow_multiple_roles && ( $profileuser || 'user-new.php' === $pagenow ) && ! is_network_admin() ) {
		$fields[] = Field::make( 'multiselect', 'wpum_user_roles', '' )
		->add_options( $roles )
		->set_default_value( $existing_roles )
		->set_classes( 'wpum-multiple-user-roles' )
		->set_help_text( esc_html__( 'Select one or more roles for this user.', 'wp-user-manager' ) );
	}

	Container::make( 'user_meta', esc_html__( 'Profile Privacy', 'wp-user-manager' ) )
			->add_fields( $fields );
}

add_action( 'carbon_fields_register_fields', 'wpum_register_profile_privacy_fields' );

add_action( 'template_redirect', 'wpum_reset_password_redirect' );

/**
 * Handle redirecting after user clicks on password reset email link
 */
function wpum_reset_password_redirect() {
	$action = filter_input( INPUT_GET, 'action' );

	if ( ! $action || 'wpum-reset' !== $action ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	if ( ! isset( $_GET['login'] ) || ! isset( $_GET['key'] ) ) { // phpcs:ignore
		return;
	}

	if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	list( $rp_path ) = explode( '?', wp_unslash( filter_input( INPUT_SERVER, 'REQUEST_URI' ) ) );

	$login = wp_unslash( filter_input( INPUT_GET, 'login' ) );
	$key   = wp_unslash( filter_input( INPUT_GET, 'key' ) );

	$value = sprintf( '%s:%s', $login, $key );
	setcookie( 'wpum-resetpass-' . COOKIEHASH, $value, 0, $rp_path, COOKIE_DOMAIN, is_ssl(), true );

	$url = remove_query_arg( array( 'key', 'login', 'action' ) );
	$url = add_query_arg( 'step', 'reset', $url );

	wp_safe_redirect( $url );
	exit;
}

/**
 * @param int $user_id
 */
function wpum_action_profile_update( $user_id ) {
	$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
	if ( ! $allow_multiple_roles ) {
		return;
	}

	if ( isset( $_POST['_wpum_user_roles'] ) && current_user_can( 'promote_user' ) ) { // phpcs:ignore

		$user = get_user_by( 'ID', $user_id );

		$roles = filter_input( INPUT_POST, '_wpum_user_roles' );

		$wpum_roles = explode( '|', $roles );
		wpum_update_roles( $wpum_roles, $user );
	}
}

add_action( 'profile_update', 'wpum_action_profile_update', 99 );
if ( is_multisite() ) {
	add_action( 'add_user_to_blog', 'wpum_action_profile_update', 99 );
} else {
	add_action( 'user_register', 'wpum_action_profile_update', 99 );
}

/**
 * @param \WP_User $user
 */
function wpum_modify_multiple_roles_ui( $user ) {
	$allow_multiple_roles = wpum_get_option( 'allow_multiple_user_roles' );
	if ( ! $allow_multiple_roles ) {
		return;
	}

	?>
	<script>
		jQuery( function( $ ) {
			if ( !$( '.user-role-wrap select#role, #createuser select#role' ).length ) {
				return;
			}
			var el_userrole = $( '.user-role-wrap select#role, #createuser select#role' );
			$( $( '.wpum-multiple-user-roles' ) ).insertAfter( el_userrole ).css( 'padding', 0 );
			$( el_userrole ).hide();
		} );
	</script>
	<?php
}

add_action( 'user_new_form', 'wpum_modify_multiple_roles_ui', 0 );
add_action( 'show_user_profile', 'wpum_modify_multiple_roles_ui', 0 );
add_action( 'edit_user_profile', 'wpum_modify_multiple_roles_ui', 0 );

/**
 * Restrict profile page when disabled.
 *
 * @return void
 */
function wpum_restrict_profile_page() {
	$profile_page = wpum_get_core_page_id( 'profile' );

	if ( $profile_page && is_page( $profile_page ) && true === boolval( wpum_get_option( 'disable_profiles' ) ) ) {
		wp_safe_redirect( home_url() );
		die();
	}
}

add_action( 'template_redirect', 'wpum_restrict_profile_page' );

/**
 * @param WPUM_Form $form
 * @param array     $values
 * @param int       $updated_user_id
 */
function wpum_flush_user_object_cache( $form, $values, $updated_user_id ) {
	wp_cache_delete( $updated_user_id, 'user_meta' );
}
add_action( 'wpum_after_custom_user_update', 'wpum_flush_user_object_cache', 100, 3 );
add_action( 'wpum_after_user_update', 'wpum_flush_user_object_cache', 100, 3 );

/**
 * @param object $data
 */
function wpum_field_conditional_logic_rules( $data ) {
	$rulesets = apply_filters( 'wpum_field_conditional_logic_rules', array(), $data );

	if ( empty( $rulesets ) ) {
		return;
	}
	?>
	<script type="text/javascript">
		(function() {
			var ruleset = <?php echo wp_json_encode( $rulesets ); ?>;
			Object.keys( ruleset ).forEach( function( fieldName ) {
				var fields = document.querySelectorAll( '.fieldset-' + fieldName );
				if ( fields.length > 0 ) {
					fields.forEach(function(field){
						field.style.display = 'none';
						field.dataset.condition = JSON.stringify( ruleset[ fieldName ] );
					});
				}
			} );
		})();
	</script>
	<?php
}

add_action( 'wpum_after_registration_form', 'wpum_field_conditional_logic_rules', 1 );
add_action( 'wpum_after_account_form', 'wpum_field_conditional_logic_rules', 1 );
add_action( 'wpum_after_custom_account_form', 'wpum_field_conditional_logic_rules', 1 );

/**
 * @param bool   $skip
 * @param string $field_key
 * @param array  $values
 * @param array  $fields
 *
 * @return bool
 */
function wpum_conditional_fields_maybe_skip_validation( $skip, $field_key, $values, $fields ) {
	$form_data         = (object) array();
	$form_data->fields = $fields;

	$rulesets = apply_filters( 'wpum_field_conditional_logic_rules', array(), $form_data );

	if ( empty( $rulesets ) || ! isset( $rulesets[ $field_key ] ) ) {
		return false;
	}

	$field_rules = $rulesets[ $field_key ];

	if ( ! $field_rules ) {
		return false;
	}

	foreach ( $field_rules as $rules ) {
		foreach ( $rules as $rule ) {
			$valid_rule = apply_filters( "wpum_conditional_field_validate_rule_{$rule['condition']}", true, $rule, $values );
			if ( ! $valid_rule ) {
				return true;
			}
		}
	}

	return false;
}

add_filter( 'wpum_form_skip_field_validation', 'wpum_conditional_fields_maybe_skip_validation', 10, 4 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_value_not_equals( $valid, $rule, $values ) {
	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( $child[ $rule['field'] ] !== $rule['value'] ) {
					return true;
				}
			}
		}

		return false;
	}

	return $values[ $rule['field'] ] !== $rule['value'];
}

add_filter( 'wpum_conditional_field_validate_rule_value_not_equals', 'wpum_validate_rule_value_not_equals', 10, 3 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_value_equals( $valid, $rule, $values ) {
	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( $child[ $rule['field'] ] === $rule['value'] ) {
					return true;
				}
			}
		}

		return false;
	}

	return $values[ $rule['field'] ] === $rule['value'];
}

add_filter( 'wpum_conditional_field_validate_rule_value_equals', 'wpum_validate_rule_value_equals', 10, 3 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_value_contains( $valid, $rule, $values ) {
	if ( isset( $values[ $rule['field'] ] ) && is_array( $values[ $rule['field'] ] ) ) {
		return in_array( $rule['value'], $values[ $rule['field'] ], true );
	}

	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( strpos( $child[ $rule['field'] ], $rule['value'] ) ) {
					return true;
				}
			}
		}

		return false;
	}

	return strpos( $values[ $rule['field'] ], $rule['value'] );
}

add_filter( 'wpum_conditional_field_validate_rule_value_contains', 'wpum_validate_rule_value_contains', 10, 3 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_has_value( $valid, $rule, $values ) {
	if ( isset( $values[ $rule['field'] ] ) && is_array( $values[ $rule['field'] ] ) ) {
		return ! empty( $values[ $rule['field'] ] );
	}

	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( '' !== $child[ $rule['field'] ] ) {
					return true;
				}
			}
		}

		return false;
	}

	return '' !== $values[ $rule['field'] ];
}

add_filter( 'wpum_conditional_field_validate_rule_has_value', 'wpum_validate_rule_has_value', 10, 3 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_has_no_value( $valid, $rule, $values ) {
	if ( isset( $values[ $rule['field'] ] ) && is_array( $values[ $rule['field'] ] ) ) {
		return empty( $values[ $rule['field'] ] );
	}

	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( '' === $child[ $rule['field'] ] ) {
					return true;
				}
			}
		}

		return false;
	}

	return '' === $values[ $rule['field'] ];
}

add_filter( 'wpum_conditional_field_validate_rule_has_no_value', 'wpum_validate_rule_has_no_value', 10, 3 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_value_greater( $valid, $rule, $values ) {
	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( $child[ $rule['field'] ] > $rule['value'] ) {
					return true;
				}
			}
		}

		return false;
	}

	return $values[ $rule['field'] ] > $rule['value'];
}

add_filter( 'wpum_conditional_field_validate_rule_value_greater', 'wpum_validate_rule_value_greater', 10, 3 );

/**
 * @param bool  $valid
 * @param array $rule
 * @param array $values
 *
 * @return bool
 */
function wpum_validate_rule_value_less( $valid, $rule, $values ) {
	if ( isset( $rule['parent'] ) && isset( $values[ $rule['parent'] ] ) && is_array( $values[ $rule['parent'] ] ) ) {
		foreach ( $values[ $rule['parent'] ] as $child ) {
			if ( isset( $child[ $rule['field'] ] ) ) {
				if ( $child[ $rule['field'] ] < $rule['value'] ) {
					return true;
				}
			}
		}

		return false;
	}

	return $values[ $rule['field'] ] < $rule['value'];
}

add_filter( 'wpum_conditional_field_validate_rule_value_less', 'wpum_validate_rule_value_less', 10, 3 );

// Ensure the global post is set for account/profile subpage§
add_action( 'wp', function () {
	global $post;

	if ( ! empty( $post ) ) {
		return;
	}

	global $wp;

	if ( ! isset( $wp->query_vars['page_id'] ) ) {
		return;
	}

	$account_id = wpum_get_core_page_id( 'account' );
	$profile_id = wpum_get_core_page_id( 'profile' );

	if ( $wp->query_vars['page_id'] === $account_id ) {
		$post = get_post( $account_id ); // phpcs:ignore

		return;
	}

	if ( $wp->query_vars['page_id'] === $profile_id ) {
		$post = get_post( $profile_id ); // phpcs:ignore
	}
}, 9 );

/**
 * AJAX handler to validate meta key for fields are unique
 */
function validate_user_meta_key() {
	global $wpdb;

	$field_id = filter_input( INPUT_POST, 'field_id', FILTER_VALIDATE_INT );

	if ( empty( $field_id ) ) {
		return;
	}

	$user_meta_key = sanitize_text_field( filter_input( INPUT_POST, 'user_meta_key' ) );
	$user_meta_key = 'wpum_' . $user_meta_key;

	$meta_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(meta_id) FROM {$wpdb->prefix}wpum_fieldmeta WHERE meta_key = 'user_meta_key' AND meta_value = %s AND wpum_field_id != %d", $user_meta_key, $field_id ) ); // phpcs:ignore

	$response['error'] = array();
	if ( intval( $meta_count ) > 0 ) {
		$response['error'][] = 'The user meta key must be unique for each field';
	}

	wp_send_json_success( $response );
}
add_action( 'wp_ajax_validate_user_meta_key', 'validate_user_meta_key' );


add_action( 'the_content', function( $content ) {
	$registration = filter_input( INPUT_GET, 'registration', FILTER_SANITIZE_STRING );
	if ( empty( $registration ) || 'success' !== $registration ) {
		return $content;
	}

	if ( is_page( wpum_get_core_page_id( 'register' ) ) ) {
		return $content;
	}

	global $post;

	if ( isset( $post ) && ( has_shortcode( $post->post_content, 'wpum_register' ) || has_block( 'wpum/registration-form', $post ) ) ) {
		return $content;
	}

	$success_message = apply_filters( 'wpum_registration_success_message', esc_html__( 'Registration complete. We have sent you a confirmation email with your details.', 'wp-user-manager' ) );

	ob_start();
	WPUM()->templates
		->set_template_data(
			array(
				'message' => $success_message,
			)
		)
		->get_template_part( 'messages/general', 'success' );

	return ob_get_clean() . $content;
} );
