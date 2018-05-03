<?php
/**
 * The Template for displaying the directory search form.
 *
 * This template can be overridden by copying it to yourtheme/wpum/directory/search-form.php
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

$value = '';

?>
<div id="wpum-directory-search-form">
	<div class="wpum-row">
		<div class="form-fields wpum-col-xs-10">
			<?php do_action( 'wpum_directory_search_form_top_fields'); ?>
				<input type="text" name="directory-search" id="wpum-directory-search" placeholder="<?php echo esc_html_e( 'Search for users' ); ?>" value="<?php echo esc_attr( $value ); ?>">
			<?php do_action( 'wpum_directory_search_form_bottom_fields' ); ?>
		</div>
		<div class="form-submit wpum-col-xs-2">
			<?php wp_nonce_field( 'directory_search_action', '_wpnonce', false, true ); ?>
			<input type="submit" id="wpum-submit-user-search" class="button wpum-button" value="<?php esc_html_e( 'Search' ); ?>">
		</div>
	</div>
</div>
