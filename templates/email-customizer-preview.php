<?php
/**
 * The Template for displaying the email within the WordPress customizer.
 * This file should rarely be modified unless you know what you're doing.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

WPUM()->templates
	->set_template_data( $data )
	->get_template_part( 'emails/header', WPUM()->emails->get_template() );

WPUM()->templates
	->set_template_data( $data )
	->get_template_part( 'emails/body', WPUM()->emails->get_template() );

WPUM()->templates
	->set_template_data( $data )
	->get_template_part( 'emails/footer', WPUM()->emails->get_template() );

?>

<?php
wp_footer();
