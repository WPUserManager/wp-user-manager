<?php
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
 *
 * @return string
 */
function wpum_email_tag_website( $user_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by email tag callback signature.
	return home_url();
}

/**
 * Parse the {sitename} tag into the email to display the site name.
 *
 * @param string $user_id
 *
 * @return string
 */
function wpum_email_tag_sitename( $user_id ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by email tag callback signature.
	return esc_html( get_bloginfo( 'name' ) );
}

/**
 * Parse the {username} tag into the email to display the user's username.
 *
 * @param string $user_id
 *
 * @return string
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
 *
 * @return string
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
 *
 * @return string
 */
function wpum_email_tag_firstname( $user_id ) {

	$firstname = get_user_meta( $user_id, 'first_name', true );

	return $firstname;
}

/**
 * Parse the {lastname} tag into the email to display the user's last name.
 *
 * @param string $user_id
 *
 * @return string
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
function wpum_email_tag_login_page_url( $user_id = false ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Required by email tag callback signature.

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
	return $plain_text_password;
}

/**
 * Parse the {recovery_url} tag into the email to display personalized password recovery url.
 *
 * @param int    $user_id
 * @param string $password_reset_key
 * @param string $plain_text_password
 * @param string $tag
 * @param string $email
 *
 * @return string
 */
function wpum_email_tag_password_recovery_url( $user_id, $password_reset_key, $plain_text_password, $tag, $email ) {

	$reset_page = wpum_get_core_page_id( 'password' );
	$reset_page = get_permalink( $reset_page );
	$reset_page = add_query_arg( array(
		'login'  => rawurlencode( $email->user_login ),
		'key'    => $password_reset_key,
		'action' => 'wpum-reset',
	), $reset_page );

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

	$emails = array(
		'registration_confirmation'       => array(
			'status'      => 'active',
			'name'        => esc_html__( 'Registration confirmation', 'wp-user-manager' ),
			'description' => esc_html__( 'This is the email that is sent to the user upon successful registration.', 'wp-user-manager' ),
			'recipient'   => esc_html__( 'User\'s email.', 'wp-user-manager' ),
			'enabled'     => true,
		),
		'registration_admin_notification' => array(
			'status'      => 'active',
			'name'        => esc_html__( 'New user notification', 'wp-user-manager' ),
			'description' => esc_html__( 'This is the email sent to the site admin when a new user registers.', 'wp-user-manager' ),
			'recipient'   => esc_html__( 'Site admin\'s email.', 'wp-user-manager' ),
			'enabled'     => true,
		),
		'password_recovery_request'       => array(
			'status'      => 'active',
			'name'        => esc_html__( 'Password recovery request', 'wp-user-manager' ),
			'description' => esc_html__( 'This is the email that is sent to the visitor upon password reset request.', 'wp-user-manager' ),
			'recipient'   => esc_html__( 'Email address of the requested user.', 'wp-user-manager' ),
			'enabled'     => true,
			'disabled'    => true,
		),
	);

	return apply_filters( 'wpum_registered_emails', $emails );
}

/**
 * Retrieve data of a stored email from the database.
 *
 * @param string|boolean $email_id
 * @param string|boolean $field_id
 *
 * @return string
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
 * Retrieve the default content of the emails shipped with the plugin.
 *
 * Used both when installing the emails into the database and as a fallback
 * for stored emails that are missing one of the fields.
 *
 * @return array
 */
function wpum_get_default_emails() {
	$emails = array(
		'registration_confirmation'       => array(
			'title'   => 'Welcome to {sitename}',
			'footer'  => '<a href="{siteurl}">{sitename}</a>',
			'content' => '<p>Hello {username}, and welcome to {sitename}. We’re thrilled to have you on board. </p>
<p>For reference, here\'s your login information:</p>
<p>Username: {username}<br />Login page: {login_page_url}<br />Password: {password}</p>
<p>Thanks,<br />{sitename}</p>',
			'subject' => 'Welcome to {sitename}',
		),
		'registration_admin_notification' => array(
			'title'   => 'New User Registration',
			'content' => '<p>New user registration on your site {sitename}:<br></p>
<p>Username: {username}</p>
<p>E-mail: {email}</p>',
			'subject' => '[{sitename}] New User Registration',
		),
		'password_recovery_request'       => array(
			'subject' => 'Reset your {sitename} password',
			'title'   => 'Reset your {sitename} password',
			'content' => '<p>Hello {username},</p>
<p>You are receiving this message because you or somebody else has attempted to reset your password on {sitename}.</p>
<p>If this was a mistake, just ignore this email and nothing will happen.</p>
<p>To reset your password, visit the following address:</p>
<p>{recovery_url}</p>',
			'footer'  => '<a href="{siteurl}">{sitename}</a>',
		),
	);

	return apply_filters( 'wpum_default_emails', $emails );
}

/**
 * Fill in the fields an email stored in the database is missing.
 *
 * The email customizer only saves the fields that were changed, so an email
 * that was edited before its defaults were installed can be stored without a
 * subject. Missing fields fall back to the default, and an empty subject does
 * too, since an email without a subject line is routinely rejected or filtered.
 *
 * @param array  $email    Stored email data.
 * @param string $email_id Email identifier, e.g. registration_confirmation.
 *
 * @return array
 */
function wpum_fill_email_defaults( $email, $email_id ) {
	if ( ! is_array( $email ) ) {
		return $email;
	}

	$defaults = wpum_get_default_emails();

	if ( isset( $defaults[ $email_id ] ) ) {
		foreach ( $defaults[ $email_id ] as $field => $default ) {
			if ( ! isset( $email[ $field ] ) ) {
				$email[ $field ] = $default;
				continue;
			}

			if ( 'subject' === $field && is_string( $email[ $field ] ) && '' === trim( $email[ $field ] ) ) {
				$email[ $field ] = $default;
			}
		}
	}

	// Emails registered by addons have no defaults here, so fall back to the
	// email heading and then to the site name rather than sending no subject.
	if ( ! isset( $email['subject'] ) || ! is_string( $email['subject'] ) || '' === trim( $email['subject'] ) ) {
		$title = isset( $email['title'] ) && is_string( $email['title'] ) ? trim( $email['title'] ) : '';

		$email['subject'] = '' !== $title ? $title : '{sitename}';
	}

	return $email;
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
	} else {
		return false;
	}

	$enabled = array_key_exists( 'enabled', $email ) ? rest_sanitize_boolean( $email['enabled'] ) : true;

	if ( ! apply_filters( 'wpum_email_enabled', $enabled, $email_id, $email ) ) {
		return false;
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

	// A stored option can be partial, e.g. when only one email's content was
	// ever saved, so restore any missing email and any missing field for every
	// consumer of the option. Stored values always override the defaults.
	$emails = array_merge( wpum_get_default_emails(), $emails );

	foreach ( $emails as $email_id => $email ) {
		if ( is_array( $email ) ) {
			$emails[ $email_id ] = wpum_fill_email_defaults( $email, $email_id );
		}
	}

	return $emails;
}

/**
 * Disable the email notification sent to the admin when a user changes the password.
 */
if ( wpum_get_option( 'disable_admin_password_recovery_email' ) && ! function_exists( 'wp_password_change_notification' ) ) {
	/**
	 * @param \WP_User $user
	 */
	function wp_password_change_notification( $user ) {
	}
}

/**
 * @param array $emails
 *
 * @return array
 */
function wpum_registered_emails_customizer( $emails ) {
	$settings = wpum_get_emails();

	foreach ( $emails as $key => $email ) {
		if ( isset( $settings[ $key ] ) ) {
			if ( array_key_exists( 'enabled', $settings[ $key ] ) ) {
				$emails[ $key ]['enabled'] = rest_sanitize_boolean( $settings[ $key ]['enabled'] ) ? 1 : 0;
			} else {
				$emails[ $key ]['enabled'] = 1;
			}
		} else {
			$emails[ $key ]['enabled'] = 1;
		}
	}

	return $emails;
}

add_filter( 'wpum_registered_emails', 'wpum_registered_emails_customizer', 20 );
