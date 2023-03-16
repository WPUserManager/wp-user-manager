<?php
/**
 * Handles the license management for each addon.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

/**
 * License
 */
class WPUM_License {

	/**
	 * Holds the addon file.
	 *
	 * @var string
	 */
	private $file;

	/**
	 * Store the addon license key.
	 *
	 * @var string
	 */
	private $license;

	/**
	 * Item name from the site.
	 *
	 * @var string
	 */
	private $item_name;

	/**
	 * Item id from the site.
	 *
	 * @var string
	 */
	private $item_id;

	/**
	 * Item shortname.
	 *
	 * @var string
	 */
	private $item_shortname;

	/**
	 * Item version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * The author of the plugin.
	 *
	 * @var string
	 */
	private $author;

	/**
	 * Api url.
	 *
	 * @var string
	 */
	private $api_url;

	/**
	 * Construction function.
	 *
	 * @param string      $file      file path.
	 * @param string      $item_name item name.
	 * @param string      $item_id
	 * @param string      $version   version of the addon.
	 * @param string      $author    author of the addon.
	 * @param string|null $_api_url
	 */
	public function __construct( $file, $item_name, $item_id, $version, $author, $_api_url = null ) {

		$this->file      = $file;
		$this->item_name = $item_name;
		$this->item_id   = $item_id;
		$this->version   = $version;
		$this->author    = $author;

		if ( false !== strpos( $item_id, '.' ) ) {
			// Fix older versions of addons that had these arguments reversed
			$this->item_id = $version;
			$this->version = $item_id;
		}

		$this->api_url = apply_filters( 'wpum_api_url', 'https://wpusermanager.com' );

		if ( ! empty( $_api_url ) ) {
			$this->api_url = $_api_url;
		}

		$this->item_shortname = 'wpum_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
		$this->license        = trim( get_option( '_' . $this->item_shortname . '_license_key', '' ) );

		$this->includes();
		$this->hooks();

	}

	/**
	 * Include the updater library.
	 */
	private function includes() {

		if ( ! class_exists( 'WPUM_EDD_SL_Plugin_Updater' ) ) {
			require_once WPUM_PLUGIN_DIR . 'includes/updates/WPUM_EDD_SL_Plugin_Updater.php';
		}

	}

	/**
	 * Hook into WordPress.
	 *
	 * @return void
	 */
	private function hooks() {

		// Register settings.
		add_filter( 'wpum_licenses_register_addon_settings', array( $this, 'settings' ), 1 );

		// Activate license.
		add_action( 'carbon_fields_theme_options_container_saved', array( $this, 'handle_activate_license' ) );

		// Deactivate license key.
		add_action( 'admin_init', array( $this, 'handle_deactivate_license' ) );

		// Updater.
		add_action( 'init', array( $this, 'auto_updater' ), 0 );

		$plugin_name = plugin_basename( $this->file );
		add_action( "after_plugin_row_{$plugin_name}", array( $this, 'plugin_page_notices' ), 10, 3 );
		add_action( "in_plugin_update_message-{$plugin_name}", array( $this, 'in_plugin_update_message' ), 10, 2 );
	}

	/**
	 * Register settings for the new addon.
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function settings( $settings ) {
		$license_data = $this->get_license_data();
		$status       = isset( $license_data['status'] ) ? $license_data['status'] : 'empty';
		$expires      = isset( $license_data['expires'] ) ? $license_data['expires'] : '';

		// translators: %1$s wpum addon name
		$new_settings[] = Field::make( 'text', $this->item_shortname . '_license_key', sprintf( __( '%1$s License Key', 'wp-user-manager' ), $this->item_name ) )
							   ->set_help_text( $this->get_status_notice( $status, $expires ) );

		return array_merge( $settings, $new_settings );
	}

	/**
	 * Activate a license.
	 *
	 * @return void
	 */
	public function handle_activate_license() {

		// Detect if license submission.
		if ( isset( $_POST['_wpum_license_submission'] ) ) { // phpcs:ignore

			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( $this->is_valid() ) {
				return;
			}

			$license = filter_input( INPUT_POST, '_' . $this->item_shortname . '_license_key', FILTER_SANITIZE_STRING );
			$license = sanitize_text_field( $license );

			if ( empty( $license ) ) {
				return;
			}

			$response = $this->activate_license( $license, home_url() );

			if ( ! is_wp_error( $response ) ) {
				// Tell WordPress to look for updates.
				set_site_transient( 'update_plugins', null );

				$data = $this->prepare_license_data( $response );

				if ( isset( $data['error'] ) ) {
					return;
				}

				$this->set_license_data( $data );
			}
		}
	}

	/**
	 * Deactivate a license
	 */
	public function handle_deactivate_license() {
		$license = filter_input( INPUT_GET, $this->item_shortname . '_deactivation' );

		if ( empty( $license ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $license, $this->item_shortname ) ) {
			return;
		}

		$response = $this->deactivate_license( home_url() );

		// make sure the response came back okay
		if ( is_wp_error( $response ) ) {
			wp_die( wp_kses_post( $response->get_error_message() ) );
		}

		if ( 'deactivated' === $response->license ) {
			delete_site_transient( $this->item_shortname . '_license_data' );
			wp_safe_redirect( add_query_arg( array( 'license' => 'deactivated' ), admin_url( 'options-general.php?page=wpum-licenses' ) ) );
		}
	}

	/**
	 * @param string $endpoint
	 * @param string $license
	 * @param string $site_url
	 *
	 * @return mixed|WP_Error|null
	 */
	protected function api_request( $endpoint, $license, $site_url ) {
		$api_params = array(
			'edd_action' => $endpoint,
			'license'    => $license,
			'item_id'    => $this->item_id,
			'url'        => $site_url,
		);

		$response = wp_remote_post( $this->api_url, array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params,
		) );

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.', 'wp-user-manager' );
			}

			return new WP_Error( 'wpum-api', $message );
		}

		return json_decode( wp_remote_retrieve_body( $response ) );

	}

	/**
	 * @param string $license
	 * @param string $site_url
	 *
	 * @return mixed|WP_Error|null
	 */
	protected function activate_license( $license, $site_url ) {
		return $this->api_request( 'activate_license', $license, $site_url );
	}

	/**
	 * @param string $site_url
	 *
	 * @return mixed|WP_Error|null
	 */
	protected function deactivate_license( $site_url ) {
		return $this->api_request( 'deactivate_license', $this->license, $site_url );
	}

	/**
	 * Trigger updates for the plugin.
	 *
	 * @return void
	 */
	public function auto_updater() {
		// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
		$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
		if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
			return;
		}

		$data = array(
			'version'   => $this->version,
			'license'   => $this->license,
			'item_id'   => $this->item_id,
			'item_name' => $this->item_name,
			'author'    => $this->author,
			'url'       => home_url(),
		);

		new WPUM_EDD_SL_Plugin_Updater( $this->api_url, $this->file, $data );
	}

	/**
	 * Retrieve a deactivation url for a given plugin license.
	 *
	 * @return string
	 */
	private function get_license_deactivation_url() {
		return wp_nonce_url( admin_url( 'options-general.php?page=wpum-licenses' ), $this->item_shortname, $this->item_shortname . '_deactivation' );
	}

	/**
	 * @param array $license_data
	 *
	 * @return array
	 */
	protected function prepare_license_data( $license_data ) {
		if ( isset( $license_data->success ) && ! (bool) $license_data->success && isset( $license_data->error ) ) {
			return array(
				'url'    => home_url(),
				'status' => $license_data->error,
			);
		}

		if ( ! isset( $license_data->license ) ) {
			return array( 'error' => 'error' );
		}

		$data = array(
			'url'    => home_url(),
			'status' => $license_data->license,
		);

		if ( isset( $license_data->success ) && $license_data->success ) {
			$data['expires'] = $license_data->expires;
		}

		return $data;
	}

	/**
	 * @param array $data
	 */
	protected function set_license_data( $data = array() ) {
		$expires = HOUR_IN_SECONDS * 12;
		if ( isset( $data['error'] ) ) {
			$expires = 60;
		}

		set_site_transient( $this->item_shortname . '_license_data', $data, $expires );
	}

	/**
	 * @return array
	 */
	protected function get_license_data() {
		if ( empty( $this->license ) ) {
			return array();
		}

		$data = get_site_transient( $this->item_shortname . '_license_data' );

		if ( ! $data ) {
			return $this->check_license();
		}

		if ( isset( $data['url'] ) && home_url() !== $data['url'] ) {
			$this->deactivate_license( $data['url'] );

			$response = $this->activate_license( $this->license, home_url() );
			if ( ! is_wp_error( $response ) ) {
				$data = $this->prepare_license_data( $response );
				$this->set_license_data( $data );
			}
		}

		return $data;
	}

	/**
	 * @return bool
	 */
	protected function is_valid() {
		$license_data = $this->get_license_data();
		if ( empty( $license_data ) || isset( $license_data['error'] ) ) {
			return false;
		}

		if ( ! isset( $license_data['status'] ) || 'valid' !== $license_data['status'] ) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	protected function check_license() {
		$response = $this->api_request( 'check_license', $this->license, home_url() );

		$data = $this->prepare_license_data( $response );

		$this->set_license_data( $data );

		return $data;
	}

	/**
	 * Add a notice when the license has not been activated yet.
	 *
	 * @param string $plugin_file
	 * @param array  $plugin_data
	 * @param string $status
	 */
	public function plugin_page_notices( $plugin_file, $plugin_data, $status ) {
		$has_update = isset( $plugin_data['new_version'] ) && version_compare( $plugin_data['Version'], $plugin_data['new_version'] ) === -1;

		$license_data   = $this->get_license_data();
		$licence_status = isset( $license_data['status'] ) ? $license_data['status'] : 'empty';
		$expires        = isset( $license_data['expires'] ) ? $license_data['expires'] : '';

		if ( 'active' === $licence_status ) {
			return;
		}

		$colspan = wp_is_auto_update_enabled_for_type( 'plugin' ) ? 4 : 3;

		$message      = false;
		$notice_class = 'notice-error';

		$status_message = $this->license_state_message( $licence_status, $expires, $has_update );
		if ( ! empty( $status_message ) ) {
			$message = $status_message;
		}

		if ( $has_update && 'valid' !== $licence_status ) {
			$plugin_name = $plugin_data['Name'];
			$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $plugin_data['slug'] . '&section=changelog&TB_iframe=true&width=600&height=800' );

			// translators: %1$s wpum addon name %2$s plugin details url %3$s thickbox HTML class names
			$notice_message = sprintf( __( 'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>.', 'wp-user-manager' ), $plugin_name, esc_url( $details_url ), sprintf( 'class="thickbox open-plugin-details-modal" aria-label="%s"', esc_attr( sprintf( __( 'View %1$s version %2$s details' ), $plugin_name, $plugin_data['new_version'] ) ) ), esc_attr( $plugin_data['new_version'] ) );
			$message        = $notice_message . ' ' . $message;
			$notice_class   = 'notice-warning';
		}

		$slug               = isset( $plugin_data['slug'] ) ? $plugin_data['slug'] : strtolower( str_replace( ' ', '-', $plugin_data['Name'] ) );
		$update_notice_wrap = '<tr id="wpum-addon-notice-' . $slug . '" class="plugin-update-tr wpum-addon-notice-tr active"><td colspan="' . $colspan . '" class="colspanchange plugin-update"><div class="notice inline update-message ' . $notice_class . ' notice-alt wpum-invalid-license"><p>%s</p></div></td></tr>';

		if ( ( empty( $this->license ) || $has_update ) && $message ) {
			echo wp_kses_post( sprintf( $update_notice_wrap, $message ) );
			?>
			<script type="application/javascript">
				(function ($) {
					$( document ).ready( function() {
						$( 'tr[data-plugin="<?php echo esc_html( $plugin_file ); ?>"]' ).addClass( 'update' );

						if ( $( '#<?php echo esc_attr( $slug ); ?>-update' ).length ) {
							<?php if ( ! $has_update ) : ?>
							$( '#wpum-addon-notice-<?php echo esc_attr( $slug ); ?>' ).remove();
							$( '#<?php echo esc_attr( $slug ); ?>-update em' ).remove();
							<?php else : ?>
							$( '#<?php echo esc_attr( $slug ); ?>-update' ).remove();
							<?php endif; ?>
						}

					} );
				})( jQuery );
			</script>
			<?php
		}
	}

	/**
	 * @return string
	 */
	protected function get_renew_url() {
		$url = sprintf( "https://wpusermanager.com/checkout/?edd_license_key={$this->license}&download_id={$this->item_id}" );

		$url .= '&utm_source=WP%20User%20Manager&utm_medium=insideplugin&utm_campaign=WP%20User%20Manager&utm_content=plugins';

		return $url;
	}

	/**
	 * Display a license reminder on the plugin list screen
	 *
	 * @param array $plugin_data
	 * @param array $response
	 */
	public function in_plugin_update_message( $plugin_data, $response ) {
		$message = '';
		if ( empty( $this->license ) ) {
			// translators: %1$s licenses page URL
			echo wp_kses_post( sprintf( __( '<a href="%1$s">Activate your license</a> to update.', 'wp-user-manager' ), esc_url( admin_url( 'options-general.php?page=wpum-licenses' ) ) ) );

			return;
		}

		$license_data = $this->get_license_data();
		$status       = isset( $license_data['status'] ) ? $license_data['status'] : 'empty';

		if ( 'expired' === $status ) {
			// translators: %1$s renewal URL
			$message = sprintf( __( '<a href="%1$s" target="_blank">Renew your license</a> to update.', 'wp-user-manager' ), $this->get_renew_url() );
		}

		if ( $message ) {
			echo '' . wp_kses_post( $message );
		}
	}

	/**
	 * Get message related to license state.
	 *
	 * @param string $status
	 * @param string $expires
	 * @param bool   $has_update
	 *
	 * @return string
	 */
	public function license_state_message( $status, $expires = '', $has_update = false ) {
		$message = '';
		if ( empty( $this->license ) && ! $has_update ) {
			// translators: %1$s activation URL %2$s wpum addon name
			return sprintf( __( 'Please <a href="%1$s">activate your license</a> to receive updates and support for the %2$s addon.', 'wp-user-manager' ), esc_url( admin_url( 'options-general.php?page=wpum-licenses' ) ), '<strong>' . $this->item_name . '</strong>' );
		}

		if ( empty( $this->license ) && $has_update ) {
			// translators: %1$s activation URL
			return sprintf( __( 'Please <a href="%1$s">activate your license</a> to update.', 'wp-user-manager' ), esc_url( admin_url( 'options-general.php?page=wpum-licenses' ) ) );
		}

		switch ( $status ) {
			case 'expired':
				// translators: %s expiry date
				$message = sprintf( __( 'Your license key expired on %s.', 'wp-user-manager' ), date_i18n( get_option( 'date_format' ), strtotime( $expires, time() ) ) ) . ' ' . sprintf( __( '<a href="%1$s" target="_blank">Renew your license</a> to get access to updates and support.', 'wp-user-manager' ), $this->get_renew_url() );
				break;
			case 'disabled':
			case 'revoked':
				// translators: %s wpum contact URL
				$message = sprintf( __( 'Your license key has been disabled. <a href="%s" target="_blank">Please contact support</a>.', 'wp-user-manager' ), 'https://wpusermanager.com/contact/' );
				break;
			case 'missing':
				$message = __( 'Invalid license. Please double-check the license and try again.', 'wp-user-manager' );
				break;
			case 'invalid':
			case 'site_inactive':
				$message = __( 'Your license is not active for this site.', 'wp-user-manager' );
				break;
			case 'item_name_mismatch':
				// translators: %s wpum addon name
				$message = sprintf( __( 'This appears to be an invalid license key for %s. Please double-check the license and try again.', 'wp-user-manager' ), $this->item_name );
				break;
			case 'no_activations_left':
				$message = __( 'Your license key has reached its activation limit.', 'wp-user-manager' );
				break;
		}

		return $message;
	}

	/**
	 * @param string $status
	 * @param string $expires
	 *
	 * @return string
	 */
	public function get_status_notice( $status, $expires = '' ) {
		$message      = $this->get_status_message( $status, $expires );
		$status_class = 'notice-error';

		if ( 'valid' === $status ) {
			$status_class = 'notice-success';
		}

		if ( ! empty( $message ) ) {
			$message = '<div class="wpum-license-message is-alt ' . $status_class . ' ' . $status . '"><p>' . $message . '</p></div>';
		}

		if ( 'valid' === $status ) {
			$inline   = __( 'License successfully activated.', 'wp-user-manager' );
			$message  = '<div class="wpum-license-message is-alt notice-success"><p>' . $inline . '</p></div>';
			$message .= '<br/><a href="' . $this->get_license_deactivation_url() . '" class="button">' . esc_html__( 'Deactivate license', 'wp-user-manager' ) . '</a>';
		}

		return $message;
	}

	/**
	 * Display a message related to the license.
	 *
	 * @param string $status
	 * @param string $expires
	 *
	 * @return string
	 */
	public function get_status_message( $status, $expires = '' ) {
		if ( empty( $this->license ) ) {
			return '';
		}

		$message = '';

		switch ( $status ) {
			case 'expired':
				// translators: %s expired license date
				$message = sprintf( __( 'Your license key expired on %s.', 'wp-user-manager' ), date_i18n( get_option( 'date_format' ), strtotime( $expires, time() ) ) ) . ' ' . sprintf( __( '<a href="%1$s" target="_blank">Renew your license</a> to get access to updates and support.', 'wp-user-manager' ), $this->get_renew_url() );
				break;
			case 'disabled':
			case 'revoked':
				// translators: %s wpum contact url
				$message = sprintf( __( 'Your license key has been disabled. <a href="%s">Please contact support</a>.', 'wp-user-manager' ), 'https://wpusermanager.com/contact/' );
				break;
			case 'missing':
				$message = __( 'Invalid license. Please double-check the license and try again.', 'wp-user-manager' );
				break;
			case 'invalid':
			case 'site_inactive':
				$message = __( 'Your license is not active for this site. Please click \'Save Changes\' to activate. ', 'wp-user-manager' );
				break;
			case 'item_name_mismatch':
				// translators: %s wpum addon name
				$message = sprintf( __( 'This appears to be an invalid license key for %s. Please double-check the license and try again.', 'wp-user-manager' ), $this->item_name );
				break;
			case 'no_activations_left':
				// translators: %1$s wpum subscription url %2$s wpum purchase history url
				$message = sprintf( __( 'Your license key has reached its activation limit. <a target="_blank" href="%1$s">Upgrade</a> the license or <a target="_blank" href="%2$s">deactivate</a> existing sites.', 'wp-user-manager' ), 'https://wpusermanager.com/checkout/subscriptions/', 'https://wpusermanager.com/checkout/purchase-history/' );
				break;
		}

		if ( empty( $message ) && 'valid' !== $status ) {
			return '';
		}

		return $message;
	}

}
