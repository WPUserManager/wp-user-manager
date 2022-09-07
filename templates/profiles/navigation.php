<?php
/**
 * The Template for displaying the profile navigation bar.
 *
 * This template can be overridden by copying it to yourtheme/wpum/profiles/navigation.php
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

?>

<nav class="profile-navbar">
	<?php foreach ( $data->tabs as $tab_key => $data_tab ) : ?>
		<a href="<?php echo esc_url( wpum_get_profile_tab_url( $data->user, $tab_key ) ); ?>" class="tab-<?php echo esc_attr( $tab_key ); ?> <?php
		if ( wpum_get_active_profile_tab() === $tab_key ) :
			?>
			active<?php endif; ?>"><?php echo esc_html( $data_tab['name'] ); ?></a>
	<?php endforeach; ?>
</nav>
