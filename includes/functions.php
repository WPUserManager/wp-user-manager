<?php
/**
 * Functions that can be used everywhere.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Retrieve pages from the database and cache them as transient.
 *
 * @return array
 */
function wpum_get_pages( $force = false ) {

	$pages = [];

	if ( ( ! isset( $_GET['page'] ) || 'wpum-settings' != $_GET['page'] ) && ! $force ) {
		return $pages;
	}

	$transient =  get_transient( 'wpum_get_pages' );

	if ( $transient ) {
		$pages = $transient;
	} else {
		$available_pages = get_pages();
		if ( ! empty( $available_pages ) ) {
			foreach ( $available_pages as $page ) {
				$pages[] = array(
					'value' => $page->ID,
					'label' => $page->post_title
				);
			}
			set_transient( 'wpum_get_pages', $pages, DAY_IN_SECONDS );
		}
	}
	return $pages;
}

/**
 * Retrieve the options for the available login methods.
 *
 * @return array
 */
function wpum_get_login_methods() {
	return apply_filters( 'wpum_get_login_methods', array(
		'username'       => __( 'Username only', 'wpum' ),
		'email'          => __( 'Email only', 'wpum' ),
		'username_email' => __( 'Username or Email', 'wpum' ),
	) );
}

/**
 * Retrieve a list of all user roles and cache them into a transient.
 *
 * @param boolean $force set to true if loading outside the wpum settings
 * @return array
 */
function wpum_get_roles( $force = false ) {

	$roles = [];

	if ( ( ! isset( $_GET['page'] ) || 'wpum-settings' != $_GET['page'] ) && ! $force ) {
		return $roles;
	}

	$transient = get_transient( 'wpum_get_roles' );

	if ( $transient ) {
		$roles = $transient;
	} else {

		global $wp_roles;
		$available_roles = $wp_roles->get_names();

		foreach ( $available_roles as $role_id => $role ) {
			if( $role_id == 'administrator' ) {
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
 * @return int $page_id the ID of the requested page.
 */
function wpum_get_core_page_id( $page = null ) {

	if( ! $page ) {
		return;
	}

	$id = null;

	switch( $page ) {
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

	$label        = esc_html__( 'Username' );
	$login_method = wpum_get_option( 'login_method' );

	if( $login_method == 'email' ) {
		$label = esc_html__( 'Email' );
	} elseif( $login_method == 'username_email' ) {
		$label = esc_html__( 'Username or email' );
	}

	return $label;

}

/**
 * Retrieve the url where to redirect the user after login.
 *
 * @return string
 */
function wpum_get_login_redirect() {

	$redirect_to = wpum_get_option( 'login_redirect' );
	$url         = home_url();

	if( ! empty( $redirect_to ) && is_array( $redirect_to ) ) {
		$url = get_permalink( $redirect_to[0] );
	}

	return apply_filters( 'wpum_get_login_redirect', esc_url( $url ) );

}

/**
 * Replace during email parsing characters.
 *
 * @param string $str
 * @return void
 */
function wpum_starmid( $str ) {
    switch ( strlen( $str ) ) {
        case 0: return false;
        case 1: return $str;
        case 2: return $str[0] . "*";
        default: return $str[0] . str_repeat( "*", strlen($str) - 2 ) . substr($str, -1);
    }
}

/**
 * Mask an email address.
 *
 * @param string $email_address
 * @return void
 */
function wpum_mask_email_address( $email_address ) {

	if ( ! filter_var( $email_address, FILTER_VALIDATE_EMAIL ) ) {
        return false;
    }

	list( $u, $d ) = explode( "@", $email_address );

	$d   = explode( ".", $d );
	$tld = array_pop( $d );
	$d   = implode( ".", $d );

    return wpum_starmid( $u ) . "@" . wpum_starmid( $d ) . ".$tld";

}

/**
 * Check if registrations are enabled on the site.
 *
 * @return boolean
 */
function wpum_is_registration_enabled() {

	$enabled = get_option( 'users_can_register' );

	return $enabled;

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
		$list = explode( "\n", str_replace( "\r", "", $list ) );
		foreach ( $list as $username ) {
			$usernames[] = $username;
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
 * @return void
 */
function wpum_log_user_in( $email_or_id ) {

	$get_by = 'id';

	if( is_email( $email_or_id ) ) {
		$get_by = 'email';
	}

	$user     = get_user_by( $get_by, $email_or_id );
	$user_id  = $user->ID;
	$username = $user->user_login;

	wp_set_current_user( $user_id, $username );
	wp_set_auth_cookie( $user_id );
	do_action( 'wp_login', $username );

}

/**
 * Send the registration confirmation email to a given user id.
 * Display the randomly generated password if any is given.
 *
 * @param int $user_id
 * @param mixed $psw
 * @return void
 */
function wpum_send_registration_confirmation_email( $user_id, $psw = false ) {

	$registration_confirmation_email = wpum_get_email( 'registration_confirmation' );

	if( ! $user_id ) {
		return;
	}

	if( is_array( $registration_confirmation_email ) && ! empty( $registration_confirmation_email ) ) {

		$user = get_user_by( 'id', $user_id );

		// The blogname option is escaped with esc_html on the way into the database in sanitize_option
		// we want to reverse this for the plain text arena of emails.
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		// Send notification to admin if not disabled.
		if ( ! wpum_get_option( 'disable_admin_register_email' ) ) {
			$message  = sprintf( esc_html__( 'New user registration on your site %s:' ), $blogname ) . "\r\n\r\n";
			$message .= sprintf( esc_html__( 'Username: %s' ), $user->user_login ) . "\r\n\r\n";
			$message .= sprintf( esc_html__( 'E-mail: %s' ), $user->user_email ) . "\r\n";
			wp_mail( get_option( 'admin_email' ), sprintf( esc_html__( '[%s] New User Registration' ), $blogname ), $message );
		}

		if( $user instanceof WP_User ) {

			$emails = new WPUM_Emails;
			$emails->__set( 'user_id', $user_id );
			$emails->__set( 'heading', $registration_confirmation_email['title'] );

			if( ! empty( $psw ) ) {
				$emails->__set( 'plain_text_password', $psw );
			}

			$email   = $user->data->user_email;
			$subject = $registration_confirmation_email['subject'];
			$message = $registration_confirmation_email['content'];
			$emails->send( $email, $subject, $message );
			$emails->__set( 'plain_text_password', null );

		}

	}

}

/**
 * Prepare file information for upload.
 *
 * @param array $file_data
 * @return void
 */
function wpum_prepare_uploaded_files( $file_data ) {
	$files_to_upload = array();
	if ( is_array( $file_data['name'] ) ) {
		foreach( $file_data['name'] as $file_data_key => $file_data_value ) {
			if ( $file_data['name'][ $file_data_key ] ) {
				$type              = wp_check_filetype( $file_data['name'][ $file_data_key ] ); // Map mime type to one WordPress recognises
				$files_to_upload[] = array(
					'name'     => $file_data['name'][ $file_data_key ],
					'type'     => $type['type'],
					'tmp_name' => $file_data['tmp_name'][ $file_data_key ],
					'error'    => $file_data['error'][ $file_data_key ],
					'size'     => $file_data['size'][ $file_data_key ]
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
 * @param  array|WP_Error      $file Array of $_FILE data to upload.
 * @param  string|array|object $args Optional arguments
 * @return stdClass|WP_Error Object containing file information, or error
 */
function wpum_upload_file( $file, $args = array() ) {
	global $wpum_upload, $wpum_uploading_file;
	include_once( ABSPATH . 'wp-admin/includes/file.php' );
	include_once( ABSPATH . 'wp-admin/includes/media.php' );
	$args = wp_parse_args( $args, array(
		'file_key'           => '',
		'file_label'         => '',
		'allowed_mime_types' => '',
	) );
	$wpum_upload         = true;
	$wpum_uploading_file = $args['file_key'];
	$uploaded_file              = new stdClass();
	if ( '' === $args['allowed_mime_types'] ) {
		$allowed_mime_types = wpum_get_allowed_mime_types( $wpum_uploading_file );
	} else {
		$allowed_mime_types = $args['allowed_mime_types'];
	}

	$file = apply_filters( 'wpum_upload_file_pre_upload', $file, $args, $allowed_mime_types );

	if ( is_wp_error( $file ) ) {
		return $file;
	}

	if ( ! in_array( $file['type'], $allowed_mime_types ) ) {
		if ( $args['file_label'] ) {
			return new WP_Error( 'upload', sprintf( __( '"%s" (filetype %s) needs to be one of the following file types: %s' ), $args['file_label'], $file['type'], implode( ', ', array_keys( $allowed_mime_types ) ) ) );
		} else {
			return new WP_Error( 'upload', sprintf( __( 'Uploaded files need to be one of the following file types: %s' ), implode( ', ', array_keys( $allowed_mime_types ) ) ) );
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
 * @return void
 */
function wpum_get_allowed_mime_types( $field = '' ){
	if ( 'company_logo' === $field ) {
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
 * @return void
 */
function wpum_sort_array_by_priority( $a, $b ) {
	if ( $a['priority'] == $b['priority'] ) {
		return 0;
	}
	return ( $a['priority'] < $b['priority'] ) ? -1 : 1;
}

/**
 * Retrieve the url of a given account tab.
 *
 * @param string $step_key
 * @return string
 */
function wpum_get_account_tab_url( $step_key ) {

	$tab_url = get_permalink();

	if( $step_key == 'logout' ) {
		$tab_url = wp_logout_url();
	} else if( $step_key == 'view' ) {
		$tab_url = get_permalink( wpum_get_core_page_id( 'profile' ) );
	} else {
		$tab_url = $tab_url . $step_key;
	}

	return apply_filters( 'wpum_get_account_tab_url', $tab_url, $step_key );

}

/**
 * Verify if a given account tab is currently active.
 *
 * @param string $step_key
 * @param string $first_tab
 * @return boolean
 */
function wpum_is_account_tab_active( $step_key, $first_tab ) {

	$active = ! empty( get_query_var( 'tab' ) ) && get_query_var( 'tab' ) == $step_key ? true : false;

	if( ! get_query_var( 'tab' ) && $step_key == $first_tab ) {
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

	$tabs = [
		'settings'  => [
			'name'     => esc_html__( 'Settings' ),
			'priority' => 0
		],
		'password' => [
			'name'     => esc_html__( 'Password' ),
			'priority' => 0,
		],
		'view' => [
			'name'     => esc_html__( 'View profile' ),
			'priority' => 0,
		],
		'logout'   => [
			'name'     => esc_html__( 'Logout' ),
			'priority' => 0
		]
	];

	uasort( $tabs, 'wpum_sort_array_by_priority' );

	return apply_filters( 'wpum_get_account_page_tabs', $tabs );

}

/**
 * Retrieve the full hierarchy of a given page or post.
 *
 * @param int $page_id
 * @return void
 */
function wpum_get_full_page_hierarchy( $page_id ) {

	$page = get_post( $page_id );

	if ( empty( $page ) || is_wp_error( $page ) ) {
		return [];
	}

	$return         = [];
	$page_obj       = [];
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
 * @since 1.0.0
 * @return array of all the structures.
 */
function wpum_get_permalink_structures() {

	$structures = array(
		'user_id' => array(
			'name'   => 'user_id',
			'label'  => _x( 'Display user ID', 'Permalink structure' ),
			'sample' => '123'
		),
		'username' => array(
			'name'   => 'username',
			'label'  => _x( 'Display username', 'Permalink structure' ),
			'sample' => _x( 'username', 'Example of permalink setting' )
		),
		'nickname' => array(
			'name'   => 'nickname',
			'label'  => _x( 'Display nickname', 'Permalink structure' ),
			'sample' => _x( 'nickname', 'Example of permalink setting' )
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

	$queried_user                = wpum_get_queried_user();
	$user_id                     = false;
	$profile_permalink_structure = get_option( 'wpum_permalink', 'user_id' );

	if( ! $queried_user && is_user_logged_in() ) {
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
			$args = array (
				'meta_key'   => 'nickname',
				'meta_value' => $queried_user
			);
			$user_query = new WP_User_Query( $args );
			$user_query = $user_query->get_results();

			if( is_array( $user_query ) && ! empty( $user_query ) ) {
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

	return wpum_get_queried_user_id() == get_current_user_id() ? true : false;

}

/**
 * Retrieve the user url for a given user.
 *
 * @param object $user instance of WP_User ( $user->data )
 * @return string
 */
function wpum_get_profile_url( $user ) {

	$page_url            = get_permalink( wpum_get_core_page_id( 'profile' ) );
	$permalink_structure = get_option( 'wpum_permalink', 'user_id' );
	$page_url            = rtrim( $page_url, '/') . '/';

	switch ( $permalink_structure ) {
		case 'user_id':
			$page_url .= $user->ID;
			break;
		case 'username':
			$page_url .= $user->user_login;
			break;
		case 'nickname':
			$page_url .= get_user_meta( $user->ID, 'nickname', true );
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

	$tabs = [
		'about'  => [
			'name'     => esc_html__( 'About' ),
			'priority' => 0
		],
		'posts'  => [
			'name'     => esc_html__( 'Posts' ),
			'priority' => 0
		],
		'comments'  => [
			'name'     => esc_html__( 'Comments' ),
			'priority' => 0
		]
	];

	if ( ! wpum_get_option( 'profile_posts' ) ) {
		unset( $tabs['posts'] );
	}
	if ( ! wpum_get_option( 'profile_comments' ) ) {
		unset( $tabs['comments'] );
	}

	uasort( $tabs, 'wpum_sort_array_by_priority' );

	return apply_filters( 'wpum_get_registered_profile_tabs', $tabs );

}

/**
 * Retrieve the url a profile tab for the given user.
 *
 * @param object $user
 * @param array $tab
 * @return string
 */
function wpum_get_profile_tab_url( $user, $tab ) {

	$url = wpum_get_profile_url( $user );
	$url .= '/' . $tab;

	return $url;

}

/**
 * Retrieve the currently active profile tab.
 * If no profile tab is found active, automatically set the first found tab as active.
 *
 * @param string $tab
 * @return void
 */
function wpum_get_active_profile_tab() {

	$first_tab   = key( wpum_get_registered_profile_tabs() );
	$profile_tab = get_query_var( 'tab', $first_tab );

	return $profile_tab;

}

/**
 * Grab posts submitted by a user within a profile
 *
 * @param int $user_id
 * @return void
 */
function wpum_get_posts_for_profile( $user_id ) {

	if( ! $user_id ) {
		return false;
	}

	$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

	$args = apply_filters( 'wpum_get_posts_for_profile', [
		'post_type' => 'post',
		'author'    => $user_id,
		'paged'     => $paged
	] );

	$query = new WP_Query( $args );

	return $query;

}

/**
 * Retrieve comments submitted by a given user.
 *
 * @param string $user_id
 * @return void
 */
function wpum_get_comments_for_profile( $user_id ) {

	if( ! $user_id ) {
		return false;
	}

	$args = apply_filters( 'wpum_get_comments_for_profile', array(
		'user_id' => $user_id,
		'status'  => 'approve',
		'number'  => '10'
	) );

	$comments = get_comments( $args );

	return $comments;

}

/**
 * Checks if guests can view profiles.
 *
 * @return bool
 */
function wpum_guests_can_view_profiles() {
	return wpum_get_option( 'guests_can_view_profiles' );
}

/**
 * Checks if members can view profiles.
 *
 * @return bool
 */
function wpum_members_can_view_profiles() {
	return wpum_get_option( 'members_can_view_profiles' );
}

/**
 * Gets a list of users orderded by most recent registration date.
 *
 * @param int     $amount amount of users to load.
 * @return void
 */
function wpum_get_recent_users( $amount ) {

	$args = array(
		'number'  => $amount,
		'order'   => 'DESC',
		'orderby' => 'registered'
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
 * @return string
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
