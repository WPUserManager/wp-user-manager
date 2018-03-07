<?php
/**
 * The Template for displaying the already logged in content..
 *
 * This template can be overridden by copying it to yourtheme/wpum/already-logged-in.php
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
if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();

?>

<div class="wpum-already-logged-in wpum-message info">
	<p><?php printf( __( 'You are currently logged in as %s.', 'wpum' ), $current_user->display_name );?>
	<a href="#"><?php esc_html_e( 'View Profile' ); ?></a> |
	<a href="#"><?php esc_html_e( 'Account settings' ); ?></a> |
	<a href="<?php echo esc_url( wpum_get_logout_url() ); ?>">Logout &raquo;</a></p>
</div>
