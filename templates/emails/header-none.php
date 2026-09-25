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

$heading = $data->heading;

?>

<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
	</head>
	<body>
		<h1><?php echo esc_html( $heading ); ?></h1>
