<?php
/**
 * The Template for displaying the account page.
 *
 * This template can be overridden by copying it to yourtheme/wpum/account.php
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

<div class="wpum-template wpum-account-page">

	<div class="wpum_one_third">
		<?php
			WPUM()->templates
				->set_template_data( array( 'steps' => wpum_get_account_page_tabs() ) )
				->get_template_part( 'account', 'tabs' );
		?>
	</div>

	<div class="wpum_two_third last">
		<?php do_action( 'wpum_account_page_content' ); ?>
	</div>

	<div class="wpum_clearfix"></div>

</div>
