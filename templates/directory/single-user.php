<?php
/**
 * The Template for displaying the directory single user item loop.
 *
 * This template can be overridden by copying it to yourtheme/wpum/directory/single-user.php
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


?>
<div class="wpum-directory-single-user">
	<div class="wpum-row">
		<div class="wpum-col-xs" id="directory-avatar">
			<?php echo get_avatar( $data->data->ID, 50 ); ?>
		</div>
		<div class="wpum-col-xs">

		</div>
		<div class="wpum-col-xs">

		</div>
	</div>
</div>
