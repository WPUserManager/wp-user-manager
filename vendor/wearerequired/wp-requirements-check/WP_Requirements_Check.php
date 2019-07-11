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
	 * @since 1.1.0
	 *
	 * @var array $args {
	 *     Requirement arguments.
	 *
	 *     @type string $title Name of the plugin.
	 *     @type string $php   Minimum required PHP version.
	 *     @type string $wp    Minimum required WordPress version.
	 *     @type string $file  Path to the main plugin file.
	 *     @type array $i18n   {
	 *         @type string $php PHP version mismatch error message.
	 *         @type string $wp  WP version mismatch error message.
	 *     }
	 * }
	 */
	private $args;

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
	 *     @type array $i18n   {
	 *         @type string $php PHP version mismatch error message.
	 *         @type string $wp  WP version mismatch error message.
	 *     }
	 * }
	 */
	public function __construct( $args ) {
		$args = (array) $args;

		$this->args = wp_parse_args(
			$args,
			array(
				'title' => '',
				'php'   => '5.2.4',
				'wp'    => '3.8',
				'file'  => null,
				'i18n'  => array()
			)
		);

		$this->args['i18n'] = wp_parse_args(
			$this->args['i18n'],
			array(
				'php' => 'The &#8220;%1$s&#8221; plugin cannot run on PHP versions older than %2$s. Please contact your host and ask them to upgrade.',
				'wp'  => 'The &#8220;%1$s&#8221; plugin cannot run on WordPress versions older than %2$s. Please update your WordPress.',
			)
		);
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
		if ( null !== $this->args['file'] ) {
			deactivate_plugins( plugin_basename( $this->args['file'] ) );
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
		if ( self::_php_at_least( $this->args['php'] ) ) {
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
		/**
		 * Filters the notice for outdated PHP versions.
		 *
		 * @since 1.1.0
		 *
		 * @param string $message The error message.
		 * @param string $title   The plugin name.
		 * @param string $php     The WordPress version.
		 */
		$message = apply_filters( 'wp_requirements_check_php_notice', $this->args['i18n']['php'], $this->args['title'], $this->args['php'] );
		?>
		<div class="error">
			<p><?php printf( $message, esc_html( $this->args['title'] ), $this->args['php'] ); ?></p>
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
		if ( self::_wp_at_least( $this->args['wp'] ) ) {
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
		/**
		 * Filters the notice for outdated WordPress versions.
		 *
		 * @since 1.1.0
		 *
		 * @param string $message The error message.
		 * @param string $title   The plugin name.
		 * @param string $php     The WordPress version.
		*/
		$message = apply_filters( 'wp_requirements_check_wordpress_notice', $this->args['i18n']['wp'], $this->args['title'], $this->args['wp'] );
		?>
		<div class="error">
			<p><?php printf( $message, esc_html( $this->args['title'] ), $this->args['wp'] ); ?></p>
		</div>
		<?php
	}
}
