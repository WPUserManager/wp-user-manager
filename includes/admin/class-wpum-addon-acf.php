<?php
/**
 * Handles registration of the ACF addons page
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2020, WP User Manager
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WPUM ACF addon code
 */
class WPUM_Addon_ACF {

	/**
	 * Hook into WordPress
	 *
	 * @return void
	 */
	public function init() {
		if ( ! class_exists( 'ACF' ) ) {
			return;
		}
		add_action( 'add_meta_boxes', array( $this, 'add_group_metabox' ) );
	}

	/**
	 * @param string $post_type
	 */
	public function add_group_metabox( $post_type ) {
		if ( 'acf-field-group' !== $post_type ) {
			return;
		}

		$mark = '<img style="width:13px; margin-right: 10px;" src="' . WPUM_PLUGIN_URL . '/assets/images/logo.svg" title="WP User Manager">';

		add_meta_box( 'wpum-acf-group-settings', $mark . __( 'WP User Manager' ), array( $this, 'setting_metabox' ), 'acf-field-group', 'side' );
	}

	/**
	 * Add metabox
	 */
	public function setting_metabox() {
		$registration_forms = WPUM()->registration_forms->get_forms();
		?>
		<table class="form-table disabled">
			<tbody>
			<tr valign="top">
				<td colspan="2">
					<strong>Registration Form</strong>
					<p><small>Add fields to the following registration forms</small></p>
					<?php foreach ( $registration_forms as $registration_form ) : ?>
						<p>
							<label>
								<input disabled="disabled" type="checkbox" name="" value="">&nbsp;<?php echo esc_html( $registration_form->get_name() ); ?>
							</label>
						</p>
					<?php endforeach; ?>
				</td>
			</tr>
			<tr valign="top">

				<td colspan="2">
					<strong>User Profile</strong>
					<p>
						<label>
							<input disabled="disabled" type="checkbox" name="" value="1">&nbsp;Show fields on user profiles
						</label>
					</p><p>
						<label>
							<input disabled="disabled" type="checkbox" name="" value="1">&nbsp;Show on profile tab
						</label>
					</p>
				</td>

			</tr>
			<tr valign="top">
				<td colspan="2">
					<strong>User Account</strong><p>
						<label>
							<input disabled="disabled"  type="checkbox" name="" value="1">&nbsp;Allow users to edit fields
						</label></p></td>
			</tr>
			</tbody>
		</table>
		<p><span class="dashicons dashicons-lock"></span>
			<?php
			// translators: %s ACF addon URL
			echo wp_kses_post( sprintf( __( 'Integrate your ACF fields with WP&nbsp;User Manager with the <a href="%s" target="_blank">ACF addon</a>.', 'wp-user-manager' ), 'https://wpusermanager.com/addons/acf?utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=edit-field-group' ) );
			?>
		</p>

		<style type="text/css">
			#wpum-acf-group-settings table.disabled { color: #888; cursor: default; }
		</style>
		<script type="application/javascript">
			(function ($) {
				$( document ).ready( function() {
					toggle_acf_setting();
				} );

				$( 'body' ).on( 'change', '.refresh-location-rule', function( e ) {
					toggle_acf_setting();
				} );

				function toggle_acf_setting() {
					$( '#wpum-acf-group-settings' ).hide();
					var show = false;
					$( '.refresh-location-rule' ).each( function( i, obj ) {
						var str = $( this ).val();
						if ( str.toLowerCase().indexOf( 'user' ) >= 0 ) {
							show = true;
							return false;
						}
					} );

					$( '#wpum-acf-group-settings' ).toggle( show );
				}
			})( jQuery );
		</script>
		<?php
	}


}

( new WPUM_Addon_ACF() )->init();
