<?php
/**
 * This is a fake email template used by the email previewer only.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$output = '{email}';

if ( isset( $data->preview ) && true === $data->preview ) {
	$output = '<div class="preview-content">' . wpum_get_email_field( $data->email_id, 'content' ) . '</div>';
}

// {email} is replaced by the content entered in the customizer.
?>
<?php
echo $output; // phpcs:ignore
