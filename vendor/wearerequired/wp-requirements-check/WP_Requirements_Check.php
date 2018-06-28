<?php
/**
 * Simple requirements checking class.
 *
 * @package WP_Requirements_Check
 */

/**
 * Simple requirements checking class.
 */
class WP_Requirements_Check {
	/**
	 * Default name of the plugin.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string
	 */
	private $title = '';

	/**
	 * Default minimum required PHP version.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string
	 */
	private $php = '5.2.4';

	/**
	 * Default minimum required WordPress version.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string
	 */
	private $wp = '3.8';

	/**
	 * Path to the main plugin file.
	 *
	 * @since 1.0.0
	 * @access private
	 *
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
	 *     @type string $php   Minimum required PHP version.
	 *     @type string $wp    Minimum required WordPress version.
	 *     @type string $file  Path to the main plugin file.
	 * }
	 */
	public function __construct( $args ) {
		foreach ( array( 'title', 'php', 'wp', 'file' ) as $setting ) {
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
		$passes = $this->php_passes() && $this->wp_passes();

		if ( ! $passes ) {
			add_action( 'admin_notices', array( $this, 'deactivate' ) );
		}

		return $passes;
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
	 * Checks if the PHP version passes the requirement.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return bool True if the PHP version is high enough, false otherwise.
	 */
	protected function php_passes() {
		if ( self::_php_at_least( $this->php ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'php_version_notice' ) );

		return false;
	}

	/**
	 * Compares the current PHP version with the minimum required version.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $min_version The minimum required version.
	 * @return bool True if the PHP version is high enough, false otherwise.
	 */
	protected static function _php_at_least( $min_version ) {
		return version_compare( PHP_VERSION, $min_version, '>=' );
	}

	/**
	 * Displays the PHP version notice.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function php_version_notice() {
		?>
		<div class="error">
			<p><?php printf( 'The &#8220;%s&#8221; plugin cannot run on PHP versions older than %s. Please contact your host and ask them to upgrade.', esc_html( $this->title ), $this->php ); ?></p>
		</div>
		<?php
	}

	/**
	 * Check if the WordPress version passes the requirement.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return bool True if the WordPress version is high enough, false otherwise.
	 */
	protected function wp_passes() {
		if ( self::_wp_at_least( $this->wp ) ) {
			return true;
		}

		add_action( 'admin_notices', array( $this, 'wp_version_notice' ) );

		return false;
	}

	/**
	 * Compare the current WordPress version with the minimum required version.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @param string $min_version Minimum required WordPress version.
	 * @return bool True if the WordPress version is high enough, false otherwise.
	 */
	protected static function _wp_at_least( $min_version ) {
		return version_compare( get_bloginfo( 'version' ), $min_version, '>=' );
	}

	/**
	 * Show the WordPress version notice.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function wp_version_notice() {
		?>
		<div class="error">
			<p><?php printf( 'The &#8220;%s&#8221; plugin cannot run on WordPress versions older than %s. Please update WordPress.', esc_html( $this->title ), $this->wp ); ?></p>
		</div>
		<?php
	}
}
