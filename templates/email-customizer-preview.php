<?php
/**
 * The Template for displaying the email within the WordPress customizer.
 * This file should rarely be modified unless you know what you're doing.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// Retrieve the selected email to modify.
$detected_email = $data->email;

WPUM()->templates
	->get_template_part( 'emails/header', WPUM()->emails->get_template() );

WPUM()->templates
	->get_template_part( 'emails/body', WPUM()->emails->get_template() );

WPUM()->templates
	->get_template_part( 'emails/footer', WPUM()->emails->get_template() );

?>

<?php wp_footer(); ?>
