<?php
    ini_set ('display_errors', 1);  
    ini_set ('display_startup_errors', 1);  
    error_reporting (E_ALL);  
/**
 * Set of functions that deals with the emails of the plugin.
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
 * Retrieve the list of available email templates.
 *
 * @return array
 */
function wpum_get_email_templates() {
	return WPUM()->emails->get_templates();
}

/**
 * Retrieve a formatted list of all registered email tags.
 *
 * @return string
 */
function wpum_get_emails_tags_list() {
	$list       = '';
	$email_tags = WPUM()->emails->get_tags();
	if ( count( $email_tags ) > 0 ) {
		foreach ( $email_tags as $email_tag ) {
			$list .= '{' . $email_tag['tag'] . '} - ' . $email_tag['description'] . '<br />';
		}
	}
	return $list;
}

/**
 * Parse the {website} tag into the email to display the site url.
 *
 * @param string $user_id
 * @return void
 */
function wpum_email_tag_website( $user_id ) {
	return home_url();
}

/**
 * Parse the {sitename} tag into the email to display the site name.
 *
 * @param string $user_id
 * @return void
 */
function wpum_email_tag_sitename( $user_id ) {
	return esc_html( get_bloginfo( 'name' ) );
}

/**
 * Parse the {username} tag into the email to display the user's username.
 *
 * @param string $user_id
 * @return void
 */
function wpum_email_tag_username( $user_id ) {

	$user     = get_user_by( 'id', $user_id );
	$username = '';

	if ( $user instanceof WP_User ) {
		$username = $user->data->user_login;
	}

	return $username;
}

/**
 * Parse the {email} tag into the email to display the user's email.
 *
 * @param string $user_id
 * @return void
 */
function wpum_email_tag_email( $user_id ) {

	$user  = get_user_by( 'id', $user_id );
	$email = '';

	if ( $user instanceof WP_User ) {
		$email = $user->data->user_email;
	}

	return $email;
}

/**
 * Parse the {firstname} tag into the email to display the user's first name.
 *
 * @param string $user_id
 * @return void
 */
function wpum_email_tag_firstname( $user_id ) {

	$firstname = get_user_meta( $user_id, 'first_name', true );

	return $firstname;
}

/**
 * Parse the {lastname} tag into the email to display the user's last name.
 *
 * @param string $user_id
 * @return void
 */
function wpum_email_tag_lastname( $user_id ) {

	$firstname = get_user_meta( $user_id, 'last_name', true );

	return $firstname;
}

/**
 * Parse the {login_page_url} tag into the email to display the site login page url.
 *
 * @param string $user_id
 *
 * @return string
 */
function wpum_email_tag_login_page_url( $user_id = false ) {

	$login_page_url = wpum_get_core_page_id( 'login' );
	$login_page_url = get_permalink( $login_page_url );

	$url = $login_page_url;

	if ( wpum_get_option( 'email_template' ) !== 'none' ) {
		$url = '<a href="' . htmlspecialchars( $login_page_url ) . '">' . esc_html( $login_page_url ) . '</a>';
	}

	return $url;
}

/**
 * @param false  $user_id
 * @param false  $password_reset_key
 * @param string $plain_text_password
 *
 * @return string
 */
function wpum_email_tag_password( $user_id = false, $password_reset_key = false, $plain_text_password = '' ) {
	return sanitize_text_field( $plain_text_password );
}

/**
 * Parse the {recovery_url} tag into the email to display personalized password recovery url.
 *
 * @param        $user_id
 * @param string $password_reset_key
 * @param        $plain_text_password
 * @param        $tag
 * @param        $email
 *
 * @return string
 */
function wpum_email_tag_password_recovery_url( $user_id, $password_reset_key, $plain_text_password, $tag, $email ) {

	$reset_page = wpum_get_core_page_id( 'password' );
	$reset_page = get_permalink( $reset_page );
	$reset_page = add_query_arg( [
		'login'  => rawurlencode( $email->user_login ),
		'key'    => $password_reset_key,
		'action' => 'wpum-reset',
	], $reset_page );

	$link_color = apply_filters( 'wpum_email_tag_password_recovery_url_color', '#000' );

	$output = $reset_page;

	if ( wpum_get_option( 'email_template' ) !== 'none' ) {
		$output = '<a href="' . htmlspecialchars( $reset_page ) . '" style="color:' . $link_color . '">' . esc_html( $reset_page ) . '</a>';
	}

	return $output;
}

/**
 * Retrieve registered emails
 *
 * @return array
 */
function wpum_get_registered_emails() {

	$emails = [
		'registration_confirmation' => [
			'status'      => 'active',
			'name'        => esc_html__( 'Registration confirmation', 'wp-user-manager' ),
			'description' => esc_html__( 'This is the email that is sent to the user upon successful registration.', 'wp-user-manager' ),
			'recipient'   => esc_html__( 'User\'s email.', 'wp-user-manager' ),
		],
		'password_recovery_request' => [
			'status'      => 'active',
			'name'        => esc_html__( 'Password recovery request', 'wp-user-manager' ),
			'description' => esc_html__( 'This is the email that is sent to the visitor upon password reset request.', 'wp-user-manager' ),
			'recipient'   => esc_html__( 'Email address of the requested user.', 'wp-user-manager' ),
		],
	];

	return apply_filters( 'wpum_registered_emails', $emails );

}

/**
 * Retrieve data of a stored email from the database.
 *
 * @param string|boolean $email_id
 * @param string|boolean $field_id
 * @return void
 */
function wpum_get_email_field( $email_id = false, $field_id = false ) {

	if ( ! $email_id || ! $field_id ) {
		return false;
	}

	$output       = false;
	$stored_email = wpum_get_emails();

	if ( ! empty( $stored_email ) && is_array( $stored_email ) && array_key_exists( $email_id, $stored_email ) ) {
		$found_email = $stored_email[ $email_id ];
		if ( array_key_exists( $field_id, $found_email ) ) {
			$output = $found_email[ $field_id ];
		}
	}

	return $output;

}

/**
 * Retrieve details about emails stored into the database.
 *
 * @param bool     $email_id
 * @param null|int $user_id
 *
 * @return bool|string
 */
function wpum_get_email( $email_id = false, $user_id = null ) {
	$email = false;

	if ( ! $email_id ) {
		return false;
	}

	$emails = wpum_get_emails();

	if ( array_key_exists( $email_id, $emails ) && is_array( $emails[ $email_id ] ) ) {
		$email = $emails[ $email_id ];
	}

	return apply_filters( 'wpum_get_email', $email, $email_id, $user_id );
}

/**
 * @return array
 */
function wpum_get_emails() {
	$emails = get_option( 'wpum_email', array() );

	if ( empty( $emails ) ) {
		$emails = wpum_install_emails();
	}

	return $emails;
}

/**
 * Disable the email notification sent to the admin when a user changes the password.
 */
if ( wpum_get_option( 'disable_admin_password_recovery_email' ) && ! function_exists( 'wp_password_change_notification' ) ) {
	function wp_password_change_notification( $user ) {
		return;
	}
}

function wpum_create_emailtemplate(){

	if ( ! wp_verify_nonce( $_POST['nonce'], 'wpum_create_email' ) ) {
		return;
	}

	$db = new WPUM_DB_Emails();

	switch ( trim( $_POST['email_recipient'] ) ) {
		case 'admin':
			$recipient = 'Admin\'s email address';
		  	break;
		case 'user':
			$recipient = 'Email address of the user.';
			break;			  
		default:
			$recipient = sanitize_text_field( $_POST['email_recipient_email'] );
	}
	
	$email_key = strtolower( sanitize_text_field( $_POST['email_key'] ) );
	$data = [
		'email_key'             => $email_key,
		'email_name'            => sanitize_text_field( $_POST['email_name'] ),
		'email_description'     => sanitize_text_field( $_POST['email_description'] ),
		'email_recipient'       => sanitize_text_field( $recipient  ),
	];

	$result = $db->insert( $data, 'emails' );

	$email_content = [
		$email_key => [
			'subject' => sanitize_text_field( $_POST['email_subject'] ),
			'title' => sanitize_text_field( $_POST['email_heading'] ),
			'content' => sanitize_text_field( $_POST['email_body'] ),
			'footer' => ''
		]
	];

	$existing_emails = get_option( 'wpum_email' );

	$emails = array_merge( $email_content, $existing_emails );
	update_option( 'wpum_email', $emails );

	wp_send_json_success(
		[
			wpum_get_registered_emails(),
		]
	);
	exit;
}
add_action( 'wp_ajax_wpum_create_emailtemplate', 'wpum_create_emailtemplate' );

function wpum_get_emails_db(){
	wp_send_json_success(
		[
			'emails'   => wpum_get_registered_emails(),
		]
	);
}
add_action( 'wp_ajax_wpum_get_emails', 'wpum_get_emails_db' );

function wpum_registered_emails_db( $emails ){
	$db = new WPUM_DB_Emails();
	$db_emails = $db->get_emails();

	foreach( $db_emails  as $email ) {
		$emails[ $email['email_key'] ] = array(
			'status' 		=> 'manual',
			'name' 			=> $email['email_name'],
			'recipient' 	=> $email['email_recipient'],
			'description' 	=> $email['email_description'],
		);
	}

	return $emails;
}

add_filter( 'wpum_registered_emails', 'wpum_registered_emails_db', 99 );