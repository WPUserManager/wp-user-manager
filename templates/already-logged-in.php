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
	<p><?php printf( __( 'You are currently logged in as %s.', 'wp-user-manager' ), $current_user->display_name );?>
	<a href="<?php echo esc_url( get_permalink( wpum_get_core_page_id( 'profile' ) ) ); ?>"><?php esc_html_e( 'View Profile', 'wp-user-manager' ); ?></a> |
	<a href="<?php echo esc_url( get_permalink( wpum_get_core_page_id( 'account' ) ) ); ?>"><?php esc_html_e( 'Account settings', 'wp-user-manager' ); ?></a> |
	<a href="<?php echo esc_url( wp_logout_url() ); ?>"><?php esc_html_e( 'Logout', 'wp-user-manager' ); ?> &raquo;</a></p>
</div>
