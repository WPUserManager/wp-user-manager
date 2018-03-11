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
 * Retrieve the heading title of a specific email.
 *
 * @param string $email_id
 * @return void
 */
function wpum_get_email_heading( $email_id = false ) {

	if( ! $email_id ) {
		return false;
	}

	$heading              = false;
	$stored_emails_option = get_option( 'wpum_email', false );

	if( ! empty( $stored_emails_option ) && array_key_exists( $email_id, $stored_emails_option ) && array_key_exists( 'title', $stored_emails_option[ $email_id ] ) ) {
		$heading = $stored_emails_option[ $email_id ][ 'title' ];
	}

	return $heading;

}
