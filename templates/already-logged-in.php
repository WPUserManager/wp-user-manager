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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_logged_in_user = wp_get_current_user();

$links = apply_filters( 'wpum_already_logged_in_links', array(
	'profile' => array(
		'url'  => get_permalink( wpum_get_core_page_id( 'profile' ) ),
		'text' => __( 'View profile', 'wp-user-manager' ),
	),
	'account' => array(
		'url'  => get_permalink( wpum_get_core_page_id( 'account' ) ),
		'text' => __( 'Account settings', 'wp-user-manager' ),
	),
	'logout'  => array(
		'url'  => wp_logout_url(),
		'text' => __( 'Log out &raquo;', 'wp-user-manager' ),
	),
) );

$count   = count( $links );
$counter = 0;
?>

<div class="wpum-already-logged-in wpum-message info">
	<p>
		<?php
		// translators: %s user display name
		echo esc_html( sprintf( __( 'You are currently logged in as %s.', 'wp-user-manager' ), $current_logged_in_user->display_name ) );
		?>
		<?php
		foreach ( $links as $template_link ) :
			$counter ++;
			?>
			<a href="<?php echo esc_url( $template_link['url'] ); ?>"><?php echo esc_html( $template_link['text'] ); ?></a> <?php echo $counter < $count ? '|' : ''; ?>
		<?php endforeach; ?>
</div>
