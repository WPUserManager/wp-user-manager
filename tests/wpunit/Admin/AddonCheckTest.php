<?php
/**
 * Tests for the addon minimum version check.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/452
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class AddonCheckTest extends WPUMTestCase {

	const TEXT_DOMAIN = 'wpum-fake-addon';

	/**
	 * Path to the fake addon's main file.
	 *
	 * @var string
	 */
	private $addon_file;

	/**
	 * Translations requested for the fake addon's text domain.
	 *
	 * @var int
	 */
	private $translations = 0;

	public function _setUp() {
		parent::_setUp();

		$dir = WP_PLUGIN_DIR . '/wpum-fake-addon';
		if ( ! is_dir( $dir ) ) {
			mkdir( $dir );
		}

		$this->addon_file = $dir . '/wpum-fake-addon.php';
		file_put_contents(
			$this->addon_file,
			"<?php\n/**\n * Plugin Name: WPUM Fake Addon\n * Description: Used by AddonCheckTest.\n * Version: 1.5.0\n * Text Domain: " . self::TEXT_DOMAIN . "\n * Domain Path: /languages\n */\n"
		);

		// The test bootstrap pins active_plugins with a pre_option filter, so
		// the fake addon is added through the same filter.
		add_filter( 'pre_option_active_plugins', array( $this, 'activate_fake_addon' ), 99 );

		add_filter( 'gettext', array( $this, 'count_translation' ), 10, 3 );
	}

	public function _tearDown() {
		remove_filter( 'gettext', array( $this, 'count_translation' ), 10 );

		remove_filter( 'pre_option_active_plugins', array( $this, 'activate_fake_addon' ), 99 );

		if ( file_exists( $this->addon_file ) ) {
			unlink( $this->addon_file );
		}
		if ( is_dir( dirname( $this->addon_file ) ) ) {
			rmdir( dirname( $this->addon_file ) );
		}

		parent::_tearDown();
	}

	/**
	 * Add the fake addon to the active plugins.
	 *
	 * @param mixed $active Active plugins from earlier filters, or false.
	 *
	 * @return array
	 */
	public function activate_fake_addon( $active ) {
		return array_merge( is_array( $active ) ? $active : array(), array( 'wpum-fake-addon/wpum-fake-addon.php' ) );
	}

	/**
	 * Spy on gettext for the fake addon's text domain.
	 *
	 * @param string $translation Translated text.
	 * @param string $text        Original text.
	 * @param string $domain      Text domain.
	 *
	 * @return string
	 */
	public function count_translation( $translation, $text, $domain ) {
		if ( self::TEXT_DOMAIN === $domain ) {
			$this->translations++;
		}

		return $translation;
	}

	/**
	 * Build a version check for the fake addon.
	 *
	 * @param string $min_version Minimum version required.
	 *
	 * @return WPUM_Addon_Check
	 */
	private function check( $min_version ) {
		return new WPUM_Addon_Check(
			array(
				'title'       => 'WPUM Fake Addon',
				'min_version' => $min_version,
				'file'        => $this->addon_file,
			)
		);
	}

	/**
	 * Issue #452: the check runs while core loads, long before init. Reading
	 * the addon's header must not translate it, because that loads the
	 * addon's text domain early and WordPress 6.7+ reports it with a
	 * _load_textdomain_just_in_time notice under the addon's name.
	 */
	public function test_issue_452_version_check_does_not_translate_the_addon_header() {
		$this->assertTrue( $this->check( '1.0.0' )->passes() );

		$this->assertSame( 0, $this->translations, 'The addon header was translated during the version check.' );
	}

	/**
	 * The version is still read from the header, so an out of date addon is
	 * still caught.
	 */
	public function test_outdated_addon_still_fails_the_check() {
		$this->assertFalse( $this->check( '2.0.0' )->passes() );
	}
}
