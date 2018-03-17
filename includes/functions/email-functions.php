<?php
/**
 * Set of functions that deals with the emails of the plugin.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

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
	$list = '';
	$email_tags = WPUM()->emails->get_tags();
	if( count( $email_tags ) > 0 ) {
		foreach( $email_tags as $email_tag ) {
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
 * Retrieve registered emails
 *
 * @return array
 */
function wpum_get_registered_emails() {

	$emails = [
		'registration_confirmation' => [
			'status'            => 'active',
			'name'              => esc_html__( 'Registration confirmation' ),
			'description'       => esc_html__( 'This is the email that is sent to the user upon successful registration.' ),
			'recipient'         => esc_html__( 'User\'s email.' ),
		],
		'password_recovery_request' => [
			'status'            => 'active',
			'name'              => esc_html__( 'Password recovery request' ),
			'description'       => esc_html__( 'This is the email that is sent to the visitor upon password reset request.' ),
			'recipient'         => esc_html__( 'Email address of the requested user.' ),
		]
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

	if( ! $email_id || ! $field_id ) {
		return false;
	}

	$output = false;
	$stored_email = get_option( 'wpum_email', false );

	if( ! empty( $stored_email ) && is_array( $stored_email ) && array_key_exists( $email_id, $stored_email ) ) {
		$found_email = $stored_email[ $email_id ];
		if( array_key_exists( $field_id, $found_email ) ) {
			$output = $found_email[ $field_id ];
		}
	}

	return $output;

}

/**
 * Retrieve details about emails stored into the database.
 *
 * @param string $email_id
 * @return void
 */
function wpum_get_email( $email_id = false ) {

	$email = false;

	if( ! $email_id ) {
		return false;
	}

	$emails = get_option( 'wpum_email', false );

	if( array_key_exists( $email_id, $emails ) && is_array( $emails[ $email_id ] ) ) {
		$email = $emails[ $email_id ];
	}

	return $email;

}
