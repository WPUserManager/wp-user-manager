<?php
/**
 * Tests the names WPUM's Elementor widgets register under.
 *
 * Elementor keys widgets by name, so a WPUM widget that shares a name with one of
 * Elementor's own is replaced by it and never shows in the panel (#565).
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ElementorWidgetNamesTest extends WPUMTestCase {

	/**
	 * Widget names Elementor and Elementor Pro register themselves.
	 *
	 * @var string[]
	 */
	private $elementor_widget_names = array(
		'login',
		'form',
		'author-box',
		'search-form',
		'nav-menu',
		'sitemap',
	);

	/**
	 * Load the stub base class if needed, then every WPUM widget class.
	 */
	public function _setUp() {
		parent::_setUp();

		require_once __DIR__ . '/elementor-stubs.php';

		$dir = WPUM_PLUGIN_DIR . 'includes/integrations/elementor/widgets/';
		require_once $dir . 'class-wpum-elementor-widget.php';
		foreach ( glob( $dir . 'class-wpum-*.php' ) as $file ) {
			require_once $file;
		}
	}

	/**
	 * Widget name for each WPUM widget class, keyed by class.
	 *
	 * @return array
	 */
	private function widget_names() {
		$names = array();
		foreach ( get_declared_classes() as $class ) {
			if ( ! is_subclass_of( $class, 'WPUM_Elementor_Widget' ) ) {
				continue;
			}
			$reflection = new ReflectionClass( $class );
			if ( $reflection->isAbstract() ) {
				continue;
			}
			$names[ $class ] = $reflection->newInstanceWithoutConstructor()->get_name();
		}

		return $names;
	}

	/**
	 * The Login Link widget mustn't use Elementor's "login" name.
	 */
	public function test_issue_565_login_link_widget_name_does_not_clash_with_elementor() {
		$names = $this->widget_names();

		$this->assertArrayHasKey( 'WPUM_LoginLink', $names );
		$this->assertNotSame( 'login', $names['WPUM_LoginLink'] );
		$this->assertSame( 'wpum-login-link', $names['WPUM_LoginLink'] );
	}

	/**
	 * No WPUM widget uses a name Elementor already registers.
	 */
	public function test_no_widget_name_clashes_with_elementor_widgets() {
		$clashes = array_intersect( $this->widget_names(), $this->elementor_widget_names );

		$this->assertSame( array(), $clashes, 'These WPUM widgets share a name with an Elementor widget.' );
	}

	/**
	 * Every WPUM widget has its own name.
	 */
	public function test_widget_names_are_unique() {
		$names = $this->widget_names();

		$this->assertGreaterThanOrEqual( 10, count( $names ) );
		$this->assertSame( count( $names ), count( array_unique( $names ) ) );
	}

	/**
	 * The other widgets keep the names existing pages were saved with.
	 */
	public function test_other_widget_names_are_unchanged() {
		$names = $this->widget_names();

		$this->assertSame( 'login-form', $names['WPUM_LoginForm'] );
		$this->assertSame( 'logout', $names['WPUM_LogoutLink'] );
		$this->assertSame( 'user-directory', $names['WPUM_UserDirectory'] );
	}
}
