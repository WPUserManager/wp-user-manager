<?php
/**
 * The Template for displaying the body section of the emails.
 *
 * This template can be overridden by copying it to yourtheme/wpum/emails/body-default.php
 *
 * HOWEVER, on occasion WPUM will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Determine the output of the content.
// If we're loading this file through the editor
// we'll show fake content so the user can edit it.
$output = '{email}';

if ( isset( $data->preview ) && true === $data->preview ) {
	$output = '<div class="preview-content">' . wpum_get_email_field( $data->email_id, 'content' ) . '</div>';
}

// {email} is replaced by the content entered in the customizer.
?>
<?php
echo $output; // phpcs:ignore
