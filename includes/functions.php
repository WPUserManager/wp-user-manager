<?php
/**
 * Functions that can be used everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Retrieve pages from the database and cache them as transient.
 *
 * @param bool $force
 *
 * @return array
 */
function wpum_get_pages( $force = false ) {
	$pages = array();

	$transient = get_transient( 'wpum_get_pages' );

	if ( $transient && ! $force ) {
		$pages = $transient;
	} else {
		$available_pages = get_pages(
			array(
				'post_status'          => 'publish,private',
				'wpum_restrict_bypass' => true,
			)
		);
		if ( ! empty( $available_pages ) ) {
			foreach ( $available_pages as $page ) {
				$pages[] = array(
					'value' => $page->ID,
					'label' => $page->post_title,
				);
			}
			set_transient( 'wpum_get_pages', $pages, DAY_IN_SECONDS );
		}
	}

	return $pages;
}

/**
 * Get page options for redirects
 *
 * @return array|array[]
 */
function wpum_get_redirect_pages() {
	$pages = wpum_get_pages();

	if ( 'posts' === get_option( 'show_on_front' ) ) {
		$homepage = array(
			array(
				'value' => 'hp',
				'label' => __( 'Homepage', 'wp-user-manager' ),
			),
		);

		$pages = array_merge( $homepage, $pages );
	}

	return $pages;
}

/**
 * Retrieve the options for the available login methods.
 *
 * @return array
 */
function wpum_get_login_methods() {
	return apply_filters(
		'wpum_get_login_methods',
		array(
			'username'       => __( 'Username only', 'wp-user-manager' ),
			'email'          => __( 'Email only', 'wp-user-manager' ),
			'username_email' => __( 'Username or Email Address' ),
		)
	);
}

/**
 * Retrieve a list of all user roles and cache them into a transient.
 *
 * @param boolean $force set to true to get the latest
 * @param boolean $admin set to true to load the admin role too
 *
 * @return array
 */
function wpum_get_roles( $force = false, $admin = false ) {
	$roles = array();

	$transient = get_transient( 'wpum_get_roles' );

	if ( $transient && ! $force ) {
		$roles = $transient;
	} else {

		global $wp_roles;
		if ( ! isset( $wp_roles ) ) {
			return $roles;
		}

		$available_roles = $wp_roles->get_names();
		foreach ( $available_roles as $role_id => $role ) {
			if ( 'administrator' === $role_id && ! $admin ) {
				continue;
			}
			$roles[] = array(
				'value' => esc_attr( $role_id ),
				'label' => esc_html( $role ),
			);
		}
		set_transient( 'wpum_get_roles', $roles, DAY_IN_SECONDS );

	}

	return $roles;
}

/**
 * Retrieve the ID of a WPUM core page.
 *
 * @param string $page Available core pages are login, register, password, account, profile.
 *
 * @return int $page_id the ID of the requested page.
 */
function wpum_get_core_page_id( $page = null ) {

	if ( ! $page ) {
		return;
	}

	$id = null;

	switch ( $page ) {
		case 'login':
			$id = wpum_get_option( 'login_page' );
			break;
		case 'register':
			$id = wpum_get_option( 'registration_page' );
			break;
		case 'password':
			$id = wpum_get_option( 'password_recovery_page' );
			break;
		case 'account':
			$id = wpum_get_option( 'account_page' );
			break;
		case 'profile':
			$id = wpum_get_option( 'profile_page' );
			break;
		case 'registration-confirmation':
			$id = wpum_get_option( 'registration_redirect' );
			break;
		case 'login-redirect':
			$id = wpum_get_option( 'login_redirect' );
			break;
		case 'logout-redirect':
			$id = wpum_get_option( 'logout_redirect' );
			break;
	}

	$id = is_array( $id ) ? $id[0] : false;

	return $id;

}

/**
 * Pluck a certain field out of each object in a list.
 *
 * This has the same functionality and prototype of
 * array_column() (PHP 5.5) but also supports objects.
 *
 * @param array      $list      List of objects or arrays
 * @param int|string $field     Field from the object to place instead of the entire object
 * @param int|string $index_key Optional. Field from the object to use as keys for the new array.
 *                              Default null.
 *
 * @return array Array of found values. If `$index_key` is set, an array of found values with keys
 *               corresponding to `$index_key`. If `$index_key` is null, array keys from the original
 *               `$list` will be preserved in the results.
 */
function wpum_list_pluck( $list, $field, $index_key = null ) {
	if ( ! $index_key ) {
		/**
		 * This is simple. Could at some point wrap array_column()
		 * if we knew we had an array of arrays.
		 */
		foreach ( $list as $key => $value ) {
			if ( is_object( $value ) ) {
				if ( isset( $value->$field ) ) {
					$list[ $key ] = $value->$field;
				}
			} else {
				if ( isset( $value[ $field ] ) ) {
					$list[ $key ] = $value[ $field ];
				}
			}
		}

		return $list;
	}

	/*
	 * When index_key is not set for a particular item, push the value
	 * to the end of the stack. This is how array_column() behaves.
	 */
	$newlist = array();
	foreach ( $list as $value ) {
		if ( is_object( $value ) ) {
			if ( isset( $value->$index_key ) ) {
				$newlist[ $value->$index_key ] = $value->$field;
			} else {
				$newlist[] = $value->$field;
			}
		} else {
			if ( isset( $value[ $index_key ] ) ) {
				$newlist[ $value[ $index_key ] ] = $value[ $field ];
			} else {
				$newlist[] = $value[ $field ];
			}
		}
	}
	$list = $newlist;

	return $list;
}

/**
 * Retrieve the correct label for the login form.
 *
 * @return string
 */
function wpum_get_login_label() {

	$label        = esc_html__( 'Username', 'wp-user-manager' );
	$login_method = wpum_get_option( 'login_method' );

	if ( 'email' === $login_method ) {
		$label = esc_html__( 'Email', 'wp-user-manager' );
	} elseif ( 'username_email' === $login_method ) {
		$label = esc_html__( 'Username or Email Address' );
	}

	return $label;

}

/**
 * Get redirect URL by option name
 *
 * @param string $option
 *
 * @return string
 */
function wpum_get_redirect_option_url( $option ) {
	$redirect_to = wpum_get_option( $option );
	$url         = false;

	if ( ! empty( $redirect_to ) && is_array( $redirect_to ) ) {
		if ( 'hp' === $redirect_to[0] ) {
			$url = home_url();
		} else {
			$page_id = apply_filters( 'wpum_redirect_page_id', $redirect_to[0], $option );
			$url     = get_permalink( $page_id );
		}
	}

	return apply_filters( 'wpum_get_' . $option, esc_url( $url ) );
}

/**
 * Retrieve the url where to redirect the user after login.
 *
 * @return string
 */
function wpum_get_login_redirect() {
	return wpum_get_redirect_option_url( 'login_redirect' );
}

/**
 * Retrieve the url where to redirect the user after registration.
 *
 * @return string
 */
function wpum_get_registration_redirect() {
	return wpum_get_redirect_option_url( 'registration_redirect' );
}


/**
 * Retrieve the url where to redirect the user after logout.
 *
 * @return string
 */
function wpum_get_logout_redirect() {
	return wpum_get_redirect_option_url( 'logout_redirect' );
}

/**
 * Replace during email parsing characters.
 *
 * @param string $str
 *
 * @return false|string
 */
function wpum_starmid( $str ) {
	switch ( strlen( $str ) ) {
		case 0:
			return false;
		case 1:
			return $str;
		case 2:
			return $str[0] . '*';
		default:
			return $str[0] . str_repeat( '*', strlen( $str ) - 2 ) . substr( $str, - 1 );
	}
}

/**
 * Mask an email address.
 *
 * @param string $email_address
 *
 * @return false|string
 */
function wpum_mask_email_address( $email_address ) {

	if ( ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
		return false;
	}

	list( $u, $d ) = explode( '@', $email_address );

	$d   = explode( '.', $d );
	$tld = array_pop( $d );
	$d   = implode( '.', $d );

	return wpum_starmid( $u ) . '@' . wpum_starmid( $d ) . ".$tld";

}

/**
 * Check if registrations are enabled on the site.
 *
 * @return boolean
 */
function wpum_is_registration_enabled() {
	$enabled = get_option( 'users_can_register' );

	return apply_filters( 'wpum_registration_enabled', $enabled );
}

/**
 * Retrieve an array of disabled usernames.
 *
 * @return array
 */
function wpum_get_disabled_usernames() {
	$usernames = array();
	if ( wpum_get_option( 'exclude_usernames' ) ) {
		$list = trim( wpum_get_option( 'exclude_usernames' ) );
		$list = explode( "\n", str_replace( "\r", '', $list ) );
		foreach ( $list as $username ) {
			$usernames[] = strtolower( $username );
		}
	}

	return array_flip( $usernames );
}

/**
 * Programmatically log a user in given an email address or user id.
 *
 * This function should usually be followed by a redirect.
 *
 * @param mixed $email_or_id
 *
 * @return void
 */
function wpum_log_user_in( $email_or_id ) {

	$get_by = 'id';

	if ( is_email( $email_or_id ) ) {
		$get_by = 'email';
	}

	$user     = get_user_by( $get_by, $email_or_id );
	$user_id  = $user->ID;
	$username = $user->user_login;

	wp_set_current_user( $user_id, $username );
	wp_set_auth_cookie( $user_id );
	do_action( 'wp_login', $username, $user );

}

/**
 * Send the registration confirmation email to a given user id.
 * Display the randomly generated password if any is given.
 *
 * @param int   $user_id
 * @param mixed $psw
 * @param bool  $password_reset_key
 *
 * @return void
 */
function wpum_send_registration_confirmation_email( $user_id, $psw = false, $password_reset_key = false ) {
	if ( ! $user_id ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );

	if ( ! $user instanceof WP_User || empty( $user->data->user_email ) ) {
		return;
	}

	wpum_send_registration_admin_email( $user );

	$registration_confirmation_email = wpum_get_email( 'registration_confirmation', $user_id );

	if ( ! is_array( $registration_confirmation_email ) || empty( $registration_confirmation_email ) ) {
		return;
	}

	if ( ! apply_filters( 'wpum_send_registration_user_email', true ) ) {
		return;
	}

	$emails = new WPUM_Emails();
	$emails->__set( 'user_id', $user_id );
	$emails->__set( 'user_login', $user->user_login );
	$emails->__set( 'heading', $registration_confirmation_email['title'] );

	if ( ! empty( $psw ) ) {
		$emails->__set( 'plain_text_password', $psw );
	}

	if ( $password_reset_key ) {
		$emails->__set( 'password_reset_key', $password_reset_key );
	}

	$email   = $user->data->user_email;
	$subject = $registration_confirmation_email['subject'];
	$message = $registration_confirmation_email['content'];

	$attachments = apply_filters( 'wpum_user_registration_confirmation_email_attachments', array(), $user );

	$emails->send( $email, $subject, $message, $attachments );
	$emails->__set( 'plain_text_password', null );
}

/**
 * @param array   $roles
 * @param WP_User $user
 * @param array   $remove_whitelist
 */
function wpum_update_roles( $roles, $user, $remove_whitelist = array() ) {
	$current_roles = $user->roles;

	if ( empty( $roles ) || ! is_array( $roles ) ) {
		return;
	}

	// Remove unselected roles
	foreach ( $current_roles as $role ) {
		if ( ( empty( $remove_whitelist ) || in_array( $role, $remove_whitelist, true ) ) && ! in_array( $role, $roles, true ) ) {
			$user->remove_role( $role );
		}
	}

	// Add new roles
	foreach ( $roles as $role ) {
		if ( ! in_array( $role, $current_roles, true ) ) {
			$user->add_role( $role );
		}
	}
}

/**
 * @param \WP_User $user
 */
function wpum_send_registration_admin_email( $user ) {
	$registration_admin_email = wpum_get_email( 'registration_admin_notification', $user->ID );

	if ( ! is_array( $registration_admin_email ) || empty( $registration_admin_email ) ) {
		return;
	}

	// Send notification to admin if not disabled.
	$disable_admin_email = wpum_get_option( 'disable_admin_register_email' );
	if ( $disable_admin_email || ! apply_filters( 'wpum_send_registration_admin_email', true ) ) {
		return;
	}

	$emails = new WPUM_Emails();
	$emails->__set( 'user_id', $user->ID );
	$emails->__set( 'user_login', $user->user_login );
	$emails->__set( 'heading', apply_filters( 'wpum_admin_registration_confirmation_email_subject', $registration_admin_email['title'], $user ) );

	$email   = apply_filters( 'wpum_admin_registration_confirmation_email_recipient', get_option( 'admin_email' ) );
	$subject = apply_filters( 'wpum_admin_registration_confirmation_email_subject', $registration_admin_email['subject'], $user );
	$message = apply_filters( 'wpum_admin_registration_confirmation_email_message', $registration_admin_email['content'], $user );

	$attachments = apply_filters( 'wpum_admin_registration_confirmation_email_attachments', array(), $user );

	$emails->send( $email, $subject, $message, $attachments );
}

/**
 * Prepare file information for upload.
 *
 * @param array $file_data
 *
 * @return array
 */
function wpum_prepare_uploaded_files( $file_data ) {
	$files_to_upload = array();
	if ( is_array( $file_data['name'] ) ) {
		foreach ( $file_data['name'] as $file_data_key => $file_data_value ) {
			if ( $file_data['name'][ $file_data_key ] ) {
				$type              = wp_check_filetype( $file_data['name'][ $file_data_key ] ); // Map mime type to one WordPress recognises
				$files_to_upload[] = array(
					'name'     => $file_data['name'][ $file_data_key ],
					'type'     => $type['type'],
					'tmp_name' => $file_data['tmp_name'][ $file_data_key ],
					'error'    => $file_data['error'][ $file_data_key ],
					'size'     => $file_data['size'][ $file_data_key ],
				);
			}
		}
	} else {
		$type              = wp_check_filetype( $file_data['name'] ); // Map mime type to one WordPress recognises
		$file_data['type'] = $type['type'];
		$files_to_upload[] = $file_data;
	}

	return apply_filters( 'wpum_prepare_uploaded_files', $files_to_upload );
}

/**
 * Uploads a file using WordPress file API.
 *
 * @param array|WP_Error      $file Array of $_FILE data to upload.
 * @param string|array|object $args Optional arguments
 *
 * @return stdClass|WP_Error Object containing file information, or error
 */
function wpum_upload_file( $file, $args = array() ) {
	global $wpum_upload, $wpum_uploading_file;
	include_once ABSPATH . 'wp-admin/includes/file.php';
	include_once ABSPATH . 'wp-admin/includes/media.php';
	$args                = wp_parse_args(
		$args,
		array(
			'file_key'           => '',
			'file_label'         => '',
			'allowed_mime_types' => '',
		)
	);
	$wpum_upload         = true;
	$wpum_uploading_file = $args['file_key'];
	$uploaded_file       = new stdClass();
	if ( '' === $args['allowed_mime_types'] ) {
		$allowed_mime_types = wpum_get_allowed_mime_types( $wpum_uploading_file );
	} else {
		$allowed_mime_types = $args['allowed_mime_types'];
	}

	$file = apply_filters( 'wpum_upload_file_pre_upload', $file, $args, $allowed_mime_types );

	if ( is_wp_error( $file ) ) {
		return $file;
	}

	$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( ! $check['ext'] || ! $check['type'] ) {
		return new WP_Error( 'upload', __( 'Sorry, you are not allowed to upload this file type.' ) );
	}

	if ( ! in_array( $file['type'], $allowed_mime_types, true ) ) {
		if ( $args['file_label'] ) {
			/* translators: %1$s: file label %2$s: file type  %3$s: allowed types */
			return new WP_Error( 'upload', sprintf( __( '"%1$s" (filetype %2$s) needs to be one of the following file types: %3$s', 'wp-user-manager' ), $args['file_label'], $file['type'], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		} else {
			/* translators: %s: allowed file types */
			return new WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s', 'wp-user-manager' ), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		}
	} else {
		$upload = wp_handle_upload( $file, apply_filters( 'submit_wpum_wp_handle_upload_overrides', array( 'test_form' => false ) ) );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'upload', $upload['error'] );
		} else {
			$uploaded_file->url       = $upload['url'];
			$uploaded_file->file      = $upload['file'];
			$uploaded_file->name      = basename( $upload['file'] );
			$uploaded_file->type      = $upload['type'];
			$uploaded_file->size      = $file['size'];
			$uploaded_file->extension = substr( strrchr( $uploaded_file->name, '.' ), 1 );
		}
	}
	$wpum_upload         = false;
	$wpum_uploading_file = '';

	return $uploaded_file;
}

/**
 * Returns mime types specifically for WPUm.
 *
 * @param string $field
 *
 * @return array
 */
function wpum_get_allowed_mime_types( $field = '' ) {
	if ( 'current_user_avatar' === $field ) {
		$allowed_mime_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
		);
	} else {
		$allowed_mime_types = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif'          => 'image/gif',
			'png'          => 'image/png',
			'pdf'          => 'application/pdf',
			'doc'          => 'application/msword',
			'docx'         => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);
	}

	return apply_filters( 'wpum_mime_types', $allowed_mime_types, $field );
}

/**
 * Sort an array by the priority key value.
 *
 * @param array $a
 * @param array $b
 *
 * @return int
 */
function wpum_sort_array_by_priority( $a, $b ) {
	if ( ! isset( $a['priority'] ) ) {
		return 1;
	}

	if ( ! isset( $b['priority'] ) ) {
		return - 1;
	}

	if ( $a['priority'] === $b['priority'] ) {
		return 0;
	}

	return ( $a['priority'] < $b['priority'] ) ? - 1 : 1;
}

/**
 * Retrieve the url of a given account tab.
 *
 * @param string $step_key
 *
 * @return string
 */
function wpum_get_account_tab_url( $step_key ) {

	$tab_url = trailingslashit( get_permalink() );

	if ( 'logout' === $step_key ) {
		$tab_url = wp_logout_url();
	} elseif ( 'view' === $step_key ) {
		$tab_url = get_permalink( wpum_get_core_page_id( 'profile' ) );
	} else {
		$tab_url = $tab_url . rawurlencode( $step_key );
	}

	return apply_filters( 'wpum_get_account_tab_url', $tab_url, $step_key );
}

/**
 * Verify if a given account tab is currently active.
 *
 * @param string $step_key
 * @param string $first_tab
 *
 * @return boolean
 */
function wpum_is_account_tab_active( $step_key, $first_tab ) {
	$active = ! empty( get_query_var( 'tab' ) ) && get_query_var( 'tab' ) === $step_key;

	if ( ! get_query_var( 'tab' ) && $step_key === $first_tab ) {
		$active = true;
	}

	return $active;
}

/**
 * Retrieve the list of tabs for the account page.
 *
 * @return array
 */
function wpum_get_account_page_tabs() {

	$tabs = array(
		'settings' => array(
			'name'     => esc_html__( 'Settings', 'wp-user-manager' ),
			'priority' => 0,
		),
		'password' => array(
			'name'     => esc_html__( 'Password', 'wp-user-manager' ),
			'priority' => 800,
		),
		'view'     => array(
			'name'     => esc_html__( 'View Profile', 'wp-user-manager' ),
			'priority' => 900,
		),
		'logout'   => array(
			'name'     => esc_html__( 'Logout', 'wp-user-manager' ),
			'priority' => 999,
		),
	);

	if ( ! wpum_get_core_page_id( 'profile' ) || boolval( wpum_get_option( 'disable_profiles' ) ) === true ) {
		unset( $tabs['view'] );
	}

	if ( wpum_get_option( 'members_can_set_privacy' ) ) {
		$tabs['privacy'] = array(
			'name'     => esc_html__( 'Profile Privacy', 'wp-user-manager' ),
			'priority' => 700,
		);
	}

	$tabs = apply_filters( 'wpum_get_account_page_tabs', $tabs );

	uasort( $tabs, 'wpum_sort_array_by_priority' );

	return $tabs;

}

/**
 * Retrieve the full hierarchy of a given page or post.
 *
 * @param int $page_id
 *
 * @return array
 */
function wpum_get_full_page_hierarchy( $page_id ) {

	$page = get_post( $page_id );

	if ( empty( $page ) ) {
		return array();
	}

	$return         = array();
	$page_obj       = array();
	$page_obj['id'] = $page_id;
	$return[]       = $page_obj;

	if ( $page->post_parent > 0 ) {
		$return = array_merge( $return, wpum_get_full_page_hierarchy( $page->post_parent ) );
	}

	return $return;

}

/**
 * Get a list of available permalink structures.
 *
 * @return array of all the structures.
 * @since 1.0.0
 */
function wpum_get_permalink_structures() {

	$structures = array(
		'user_id'  => array(
			'name'   => 'user_id',
			'label'  => _x( 'Display user ID', 'Permalink structure', 'wp-user-manager' ),
			'sample' => '123',
		),
		'username' => array(
			'name'   => 'username',
			'label'  => _x( 'Display username', 'Permalink structure', 'wp-user-manager' ),
			'sample' => _x( 'username', 'Example of permalink setting', 'wp-user-manager' ),
		),
		'nickname' => array(
			'name'   => 'nickname',
			'label'  => _x( 'Display nickname', 'Permalink structure', 'wp-user-manager' ),
			'sample' => _x( 'nickname', 'Example of permalink setting', 'wp-user-manager' ),
		),
	);

	return apply_filters( 'wpum_get_permalink_structures', $structures );
}

/**
 * Retrieve the currently queried profile.
 * If no profile is queried and the user is currently logged in,
 * retrieve the current user id.
 *
 * @return mixed
 */
function wpum_get_queried_user() {

	$queried_user = get_query_var( 'profile', false );

	return $queried_user;

}

/**
 * Always retrieve the id of a queried user.
 *
 * @return mixed
 */
function wpum_get_queried_user_id() {

	$queried_user = urldecode( wpum_get_queried_user() );

	$user_id                     = false;
	$profile_permalink_structure = get_option( 'wpum_permalink', 'user_id' );

	if ( ! $queried_user && is_user_logged_in() ) {
		return get_current_user_id();
	}

	switch ( $profile_permalink_structure ) {
		case 'user_id':
			$user    = get_user_by( 'id', $queried_user );
			$user_id = $user instanceof WP_User ? absint( $user->data->ID ) : false;
			break;
		case 'username':
			$user    = get_user_by( 'login', $queried_user );
			$user_id = $user instanceof WP_User ? absint( $user->data->ID ) : false;
			break;
		case 'nickname':
			$args       = array(
				'meta_key'   => 'nickname',
				'meta_value' => $queried_user,
			);
			$user_query = new WP_User_Query( $args );
			$user_query = $user_query->get_results();

			if ( is_array( $user_query ) && ! empty( $user_query ) ) {
				$user_id = absint( $user_query[0]->data->ID );
			}

			break;
	}

	return $user_id;

}

/**
 * Check if the profile current user is visiting his own profile.
 *
 * @return boolean
 */
function wpum_is_own_profile() {
	return wpum_get_queried_user_id() === get_current_user_id();
}

/**
 * Retrieve the user url for a given user.
 *
 * @param object $user instance of WP_User ( $user->data )
 *
 * @return string
 */
function wpum_get_profile_url( $user ) {

	$page_url            = get_permalink( wpum_get_core_page_id( 'profile' ) );
	$permalink_structure = get_option( 'wpum_permalink', 'user_id' );
	$page_url            = rtrim( $page_url, '/' ) . '/';

	switch ( $permalink_structure ) {
		case 'user_id':
			$page_url .= rawurlencode( $user->ID );
			break;
		case 'username':
			$page_url .= rawurlencode( $user->user_login );
			break;
		case 'nickname':
			$page_url .= rawurlencode( get_user_meta( $user->ID, 'nickname', true ) );
			break;
	}

	return $page_url;

}

/**
 * Retrieve the tabs for the profile page.
 *
 * @return array
 */
function wpum_get_registered_profile_tabs() {

	$tabs = array(
		'about'    => array(
			'name'     => esc_html__( 'About', 'wp-user-manager' ),
			'priority' => 0,
		),
		'posts'    => array(
			'name'     => esc_html__( 'Posts', 'wp-user-manager' ),
			'priority' => 1,
		),
		'comments' => array(
			'name'     => esc_html__( 'Comments', 'wp-user-manager' ),
			'priority' => 2,
		),
	);

	if ( ! wpum_get_option( 'profile_posts' ) ) {
		unset( $tabs['posts'] );
	}
	if ( ! wpum_get_option( 'profile_comments' ) ) {
		unset( $tabs['comments'] );
	}

	$tabs = apply_filters( 'wpum_get_registered_profile_tabs', $tabs );

	uasort( $tabs, 'wpum_sort_array_by_priority' );

	return $tabs;

}

/**
 * Retrieve the url a profile tab for the given user.
 *
 * @param \WP_User $user
 * @param string   $tab
 *
 * @return string
 */
function wpum_get_profile_tab_url( $user, $tab ) {
	$url = wpum_get_profile_url( $user );

	$url .= '/' . $tab;

	return apply_filters( 'wpum_get_profile_tab_url', $url, $tab, $user );
}

/**
 * Retrieve the currently active profile tab.
 * If no profile tab is found active, automatically set the first found tab as active.
 *
 * @return string
 */
function wpum_get_active_profile_tab() {
	$first_tab   = key( wpum_get_registered_profile_tabs() );
	$profile_tab = get_query_var( 'tab', $first_tab );

	return $profile_tab;
}

/**
 * Grab posts submitted by a user within a profile
 *
 * @param int    $user_id
 * @param string $post_type
 *
 * @return false|WP_Query
 */
function wpum_get_posts_for_profile( $user_id, $post_type = 'post' ) {
	if ( ! $user_id ) {
		return false;
	}

	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	$args = apply_filters(
		'wpum_get_posts_for_profile',
		array(
			'post_type'   => $post_type,
			'author'      => $user_id,
			'paged'       => $paged,
			'post_status' => 'publish',
		)
	);

	$query = new WP_Query( $args );

	return $query;
}

/**
 * Retrieve comments submitted by a given user.
 *
 * @param string $user_id
 *
 * @return false|int|int[]|WP_Comment[]
 */
function wpum_get_comments_for_profile( $user_id ) {

	if ( ! $user_id ) {
		return false;
	}

	$comments = array();
	$per_page = wpum_get_option( 'number_of_comments', 10 );
	$paged    = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
	$offset   = ( ( $paged - 1 ) * $per_page );

	$args = apply_filters(
		'wpum_get_comments_for_profile',
		array(
			'user_id' => $user_id,
			'status'  => 'approve',
			'number'  => $per_page,
			'offset'  => $offset,
		)
	);

	$comment_count = get_comments(
		array(
			'user_id' => $args['user_id'],
			'status'  => $args['status'],
			'count'   => true,
		)
	);

	$num_pages = ceil( $comment_count / $per_page );

	$comments['current'] = $paged;
	$comments['total']   = $num_pages;
	$comments['items']   = get_comments( $args );

	return $comments;

}

/**
 * Checks if guests can view profiles.
 *
 * @param int $user_id
 *
 * @return bool
 */
function wpum_guests_can_view_profiles( $user_id ) {
	if ( \WPUM\carbon_get_user_meta( $user_id, 'hide_profile_guests' ) ) {
		return false;
	}

	return wpum_get_option( 'guests_can_view_profiles' );
}

/**
 * Checks if members can view profiles.
 *
 * @param int $user_id
 *
 * @return bool
 */
function wpum_members_can_view_profiles( $user_id ) {
	if ( \WPUM\carbon_get_user_meta( $user_id, 'hide_profile_members' ) ) {
		return false;
	}

	return wpum_get_option( 'members_can_view_profiles' );
}

/**
 * Gets a list of users orderded by most recent registration date.
 *
 * @param int $amount amount of users to load.
 *
 * @return array
 */
function wpum_get_recent_users( $amount ) {

	$args = array(
		'number'  => $amount,
		'order'   => 'DESC',
		'orderby' => 'registered',
	);

	// The Query
	$user_query = new WP_User_Query( apply_filters( 'wpum_get_recent_users', $args ) );

	// Get the results
	$users = $user_query->get_results();

	return $users;
}

/**
 * Inline css for the fancy WPUM admin notices.
 *
 * @return void
 */
function wpum_custom_admin_notice_inline_css() {

	?>
	<style>
		.notice.wpum-notice {
			border-left-color: #008ec2 !important;
			padding: 20px;
		}

		.rtl .notice.wpum-notice {
			border-right-color: #008ec2 !important;
		}

		.notice.notice.wpum-notice .wpum-notice-inner {
			display: table;
			width: 100%;
		}

		.notice.wpum-notice .wpum-notice-inner .wpum-notice-icon,
		.notice.wpum-notice .wpum-notice-inner .wpum-notice-content,
		.notice.wpum-notice .wpum-notice-inner .wpum-install-now {
			display: table-cell;
			vertical-align: middle;
		}

		.notice.wpum-notice .wpum-notice-icon {
			color: #509ed2;
			font-size: 50px;
			width: 32px;
		}

		.notice.wpum-notice .wpum-notice-icon img {
			width: 32px;
		}

		.notice.wpum-notice .wpum-notice-content {
			padding: 0 40px 0 20px;
		}

		.notice.wpum-notice p {
			padding: 0;
			margin: 0;
		}

		.notice.wpum-notice h3 {
			margin: 0 0 5px;
		}

		.notice.wpum-notice .wpum-install-now {
			text-align: center;
		}

		.notice.wpum-notice .wpum-install-now .wpum-install-button {
			padding: 6px 50px;
			height: auto;
			line-height: 20px;
		}

		.notice.wpum-notice a.no-thanks {
			display: block;
			margin-top: 10px;
			color: #72777c;
			text-decoration: none;
		}

		.notice.wpum-notice a.no-thanks:hover {
			color: #444;
		}

		@media (max-width: 767px) {

			.notice.notice.wpum-notice .wpum-notice-inner {
				display: block;
			}

			.notice.wpum-notice {
				padding: 20px !important;
			}

			.notice.wpum-noticee .wpum-notice-inner {
				display: block;
			}

			.notice.wpum-notice .wpum-notice-inner .wpum-notice-content {
				display: block;
				padding: 0;
			}

			.notice.wpum-notice .wpum-notice-inner .wpum-notice-icon {
				display: none;
			}

			.notice.wpum-notice .wpum-notice-inner .wpum-install-now {
				margin-top: 20px;
				display: block;
				text-align: left;
			}

			.notice.wpum-notice .wpum-notice-inner .no-thanks {
				display: inline-block;
				margin-left: 15px;
			}
		}
	</style>
	<?php

}

/**
 * Setup the default custom user meta field keys within the database
 * for the directories.
 *
 * @return void
 */
function wpum_setup_default_custom_search_fields() {

	WPUM()->search_meta->insert(
		array(
			'meta_key' => 'first_name',
		)
	);

	WPUM()->search_meta->insert(
		array(
			'meta_key' => 'last_name',
		)
	);

}

/**
 * Retrieve a list of allowed users role on the registration page
 *
 * @param array $selected_roles
 *
 * @return array $roles An array of the roles
 */
function wpum_get_allowed_user_roles( $selected_roles = array() ) {
	global $wp_roles;

	if ( ! isset( $wp_roles ) ) {
		$wp_roles = new WP_Roles(); // phpcs:ignore
	}

	$user_roles         = array();
	$allowed_user_roles = is_array( $selected_roles ) ? $selected_roles : array( $selected_roles );

	foreach ( $allowed_user_roles as $role ) {
		$user_roles[ $role ] = $wp_roles->roles[ $role ]['name'];
	}

	return $user_roles;
}

/**
 * Retrieve a list of mime types options for the file fields editor.
 *
 * @return array
 */
function wpum_get_mime_types_for_selection() {

	$types = array();

	$mimes = get_allowed_mime_types();

	foreach ( $mimes as $key => $type ) {
		$types[] = array(
			'value' => $type,
			'name'  => $key,
		);
	}

	return $types;

}

if ( ! function_exists( 'wp_new_user_notification' ) ) {
	/**
	 * Sends WPUM email notification by replacing the core emails.
	 *
	 * @param string $user_id        the user id.
	 * @param string $plaintext_pass password.
	 *
	 * @return void
	 */
	function wp_new_user_notification( $user_id, $plaintext_pass = '' ) {

		$password_set_by_admin = false;
		if ( isset( $_POST['pass1-text'] ) ) { // phpcs:ignore
			$password_set_by_admin = filter_input( INPUT_POST, 'pass1-text' );
		}
		if ( empty( $password_set_by_admin ) && isset( $_POST['pass1'] ) ) { // phpcs:ignore
			$password_set_by_admin = filter_input( INPUT_POST, 'pass1' );
		}

		$password = false;
		if ( is_admin() && current_user_can( 'create_users' ) && $password_set_by_admin ) {
			$password = sanitize_text_field( $password_set_by_admin );
		}

		if ( empty( $password ) && isset( $_POST['password'] ) ) { // phpcs:ignore
			$password = sanitize_text_field( filter_input( INPUT_POST, 'password' ) );
		}

		if ( empty( $password ) ) {
			$password = apply_filters( 'wpum_new_user_notification_password', $password, $user_id );
		}

		if ( empty( $password ) && apply_filters( 'wpum_new_user_notification_generate_password', true, $user_id ) ) {
			$password = wp_generate_password( 24, true, true );
			wp_set_password( $password, $user_id );
		}

		$user               = get_user_by( 'id', $user_id );
		$password_reset_key = get_password_reset_key( $user );

		wpum_send_registration_confirmation_email( $user_id, $password, $password_reset_key );
	}
}

/**
 * Get a specific registration form or the default
 *
 * @param null|int $form_id
 *
 * @return WPUM_Registration_Form
 */
function wpum_get_registration_form( $form_id = null ) {
	if ( empty( $form_id ) ) {
		$form = WPUM()->registration_forms->get_forms();

		return $form[0];
	}

	return new \WPUM_Registration_Form( $form_id );
}

/**
 * @return array
 */
function wpum_get_display_name_options() {
	return array(
		array(
			'value' => 'display_username',
			'label' => 'Username',
		),
		array(
			'value' => 'display_firstname',
			'label' => 'First name',
		),
		array(
			'value' => 'display_lastname',
			'label' => 'Last name',
		),
		array(
			'value' => 'display_firstlast',
			'label' => 'First and last name',
		),
		array(
			'value' => 'display_lastfirst',
			'label' => 'Last and first name',
		),
	);
}

/**
 * @param string $content
 *
 * @return string
 */
function wpum_strip_slashes( $content ) {
	$content = preg_replace( "/\\\+'/", "'", $content );
	$content = preg_replace( '/\\\+"/', '"', $content );
	$content = preg_replace( '/\\\+/', '\\', $content );

	return $content;
}
