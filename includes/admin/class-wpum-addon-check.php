<?php
/**
 * WPUM Addon Version Check
 *
 * Copyright (c) 2022 WP USer Manager
 */

class WPUM_Addon_Check {

	/**
	 * Default name of the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $title = '';

	/**
	 * The version of the addon that is required.
	 *
	 * @var string
	 */
	private $min_version = '';

	/**
	 * Path to the main plugin file.
	 *
	 * @since 1.0.0
	 * @access private
	 * @var string
	 */
	private $file;

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $args {
	 *     An array of arguments to overwrite the default requirements.
	 *
	 *     @type string $title Name of the plugin.
	 *     @type string $version Minimum required PHP version.
	 *     @type string $file  Path to the main plugin file.
	 * }
	 */
	public function __construct( $args ) {
		foreach ( array( 'title', 'min_version', 'file' ) as $setting ) {
			if ( isset( $args[ $setting ] ) ) {
				$this->$setting = $args[ $setting ];
			}
		}
	}

	/**
	 * Check if the install passes the requirements.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return bool True if the install passes the requirements, false otherwise.
	 */
	public function passes() {
		$passes = $this->addon_passes();
		if ( ! $passes ) {
			add_action( 'admin_notices', array( $this, 'deactivate' ) );
		}
		return $passes;
	}

	/**
	 * Verify the installed version of addon is the one WPUM.
	 *
	 * @return boolean
	 */
	protected function addon_passes() {
		if ( ! file_exists( $this->file ) ) {
			// Addon not installed
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( str_replace( WP_PLUGIN_DIR . '/', '', $this->file ) ) ) {
			// Addon not activated
			return true;
		}

		$plugin_data = get_plugin_data($this->file  );
		if ( empty( $plugin_data ) || ! isset( $plugin_data['Version'] ) || empty( $plugin_data['Version'] ) ) {
			// Can't get addon version
			return true;
		}

		if ( $this->addon_at_least( $plugin_data['Version'], $this->min_version ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'addon_version_notice' ) );

		return false;
	}

	/**
	 * Detect installed version of WPUM.
	 *
	 * @param string $installed_version
	 * @param string $required_min_version
	 *
	 * @return bool
	 */
	protected function addon_at_least( $installed_version, $required_min_version ) {
		return version_compare( $installed_version, $required_min_version, '>=' );
	}

	/**
	 * Deactivates the plugin again.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function deactivate() {
		if ( null !== $this->file ) {
			deactivate_plugins( plugin_basename( $this->file ) );
		}
	}

	/**
	 * Show the WordPress version notice.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function addon_version_notice() {
		$basename   = plugin_basename( $this->file );
		$update_url = wp_nonce_url( admin_url() . 'update.php?action=upgrade-plugin&plugin=' . urlencode( $basename ), 'upgrade-plugin_' . $basename );
		?>
		<div class="error">
			<p><?php printf( '<strong>WP User Manager</strong> &mdash; %s addon has been deactivated as it cannot run on WP User Manager %s. Please <a href="%s">update</a> the addon.', esc_html( $this->title ), WPUM_VERSION, $update_url ); ?></p>
		</div>
		<?php
	}

}
