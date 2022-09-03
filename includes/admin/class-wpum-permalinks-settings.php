<?php
/**
 * Register new options within the permalink settings page.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permalink settings
 */
class WPUM_Permalinks_Settings {

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'add_new_permalink_settings' ) );
			add_action( 'admin_init', array( $this, 'save_structure' ) );
		}
	}

	/**
	 * Adds new settings section to the permalink options page.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function add_new_permalink_settings() {
		// Add a section to the permalinks page
		add_settings_section( 'wpum-permalink', esc_html__( 'User profiles permalink base', 'wp-user-manager' ), array( $this, 'display_settings' ), 'permalink' );
	}

	/**
	 * Displays the new settings section into the permalinks page.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function display_settings() {
		$structures      = wpum_get_permalink_structures();
		$saved_structure = get_option( 'wpum_permalink', 'user_id' );
		?>

		<?php if ( '' === get_option( 'permalink_structure', '' ) ) { ?>

		<p>
			<?php
			// translators: %s permalink settings URL
			echo wp_kses_post( sprintf( __( 'You must <a href="%s">change your permalinks</a> to anything else other than "default" for profiles to work.', 'wp-user-manager' ), admin_url( 'options-permalink.php' ) ) );
			?>
		</p>

		<?php } else { ?>

			<p><?php echo wp_kses_post( 'These settings control the permalinks used for users profiles. These settings only apply when <strong>not using "default" permalinks above</strong>.', 'wp-user-manager' ); ?></p>

			<table class="form-table">
				<tbody>
					<?php foreach ( $structures as $key => $settings ) : ?>
						<tr>
							<th>
								<label>
									<input name="user_permalink" type="radio" value="<?php echo esc_html( $settings['name'] ); ?>" <?php checked( $settings['name'], $saved_structure ); ?> />
									<?php echo esc_html( $settings['label'] ); ?>
								</label>
							</th>
							<td>
								<code>
									<?php echo esc_url( get_permalink( wpum_get_core_page_id( 'profile' ) ) ); ?><?php echo esc_html( $settings['sample'] ); ?>
								</code>
							</td>
						</tr>
					<?php endforeach; ?>
					<input type="hidden" name="wpum-action" value="save_permalink_structure"/>
				</tbody>
			</table>
			<?php
		}
	}

	/**
	 * Saves the permalink structure.
	 *
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function save_structure() {
		// Check everything is correct.
		if ( ! is_admin() ) {
			return;
		}

		if ( ! isset( $_POST['user_permalink'] ) ) {
			return;
		}

		if ( ! check_admin_referer( 'update-permalink' ) ) {
			return;
		}

		// Bail if no cap
		if ( ! current_user_can( 'manage_options' ) ) {
			_doing_it_wrong( __FUNCTION__, esc_html( _x( 'You have no rights to access this page', '_doing_it_wrong error message', 'wp-user-manager' ) ), '1.0.0' );
			return;
		}

		// Check that the saved permalink method is one of the registered structures.
		$user_permalink = filter_input( INPUT_POST, 'user_permalink', FILTER_SANITIZE_STRING );
		if ( array_key_exists( $user_permalink, wpum_get_permalink_structures() ) ) {
			$user_permalink = sanitize_text_field( $user_permalink );
			update_option( 'wpum_permalink', $user_permalink );
		}
	}

}

new WPUM_Permalinks_Settings();
