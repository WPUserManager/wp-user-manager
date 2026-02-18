<?php
/**
 * The Template for displaying the account page tabs navigation.
 *
 * This template can be overridden by copying it to yourtheme/wpum/account-tabs.php
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

$first_key = key( $data->steps );

?>
<nav id="wpum-account-forms-tabs" class="wpum-template wpum-account-navigation">
	<ul>
		<?php foreach ( $data->steps as $step_key => $step ) : ?>
			<li class="
			<?php
			if ( wpum_is_account_tab_active( $step_key, $first_key ) ) :
				?>
				active<?php endif; ?> tab-<?php echo esc_attr( $step_key ); ?>">
				<a href="<?php echo esc_url( wpum_get_account_tab_url( $step_key ) ); ?>" class="
									<?php
									if ( wpum_is_account_tab_active( $step_key, $first_key ) ) :
										?>
					current-tab<?php endif; ?> tab-<?php echo esc_attr( $step_key ); ?>"><?php echo esc_html( $step['name'] ); ?></a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
