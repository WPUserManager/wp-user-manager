<?php
/**
 * Tests for the Elementor widget restriction controls.
 *
 * Elementor isn't installed in the test environment, so the two Elementor
 * classes the widgets need are stubbed. Only widget names are checked.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ElementorRestrictionControlsTest extends WPUMTestCase {

	/**
	 * Load the restriction controls and every WPUM Elementor widget class.
	 */
	public function _setUp() {
		parent::_setUp();

		if ( ! class_exists( '\Elementor\Widget_Base' ) ) {
			eval( 'namespace Elementor; abstract class Widget_Base {}' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		}

		if ( ! class_exists( '\Elementor\Controls_Manager' ) ) {
			eval( 'namespace Elementor; class Controls_Manager { const TAB_CONTENT = "content"; const SELECT = "select"; const SELECT2 = "select2"; const SWITCHER = "switcher"; const TEXT = "text"; const NUMBER = "number"; }' ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
		}

		$dir = WPUM_PLUGIN_DIR . 'includes/integrations/elementor/';
		require_once $dir . 'extensions/class-wpum-restrictioncontrols.php';
		require_once $dir . 'widgets/class-wpum-elementor-widget.php';
		foreach ( glob( $dir . 'widgets/class-wpum-*.php' ) as $file ) {
			require_once $file;
		}
	}

	/**
	 * Get the names of all WPUM Elementor widgets.
	 *
	 * @return array
	 */
	private function widget_names() {
		$classes = array(
			'WPUM_AccountPage',
			'WPUM_GroupDirectory',
			'WPUM_LoginForm',
			'WPUM_LoginLink',
			'WPUM_LogoutLink',
			'WPUM_PostForm',
			'WPUM_ProfileCard',
			'WPUM_ProfilePage',
			'WPUM_RecentlyRegisteredUsers',
			'WPUM_RecoveryForm',
			'WPUM_RegistrationForm',
			'WPUM_UserDirectory',
		);

		return array_map(
			function ( $class_name ) {
				return ( new $class_name() )->get_name();
			},
			$classes
		);
	}

	/**
	 * Get the widget names that get the restriction controls.
	 *
	 * @return array
	 */
	private function restricted_widgets() {
		$controls = WPUM_RestrictionControls::get_instance();
		$property = new ReflectionProperty( $controls, 'restricted_elementor_widgets' );
		$property->setAccessible( true );

		return $property->getValue( $controls );
	}

	/**
	 * Every widget in the restriction list must match a real widget name.
	 */
	public function test_restricted_widgets_match_widget_names() {
		$names = $this->widget_names();

		foreach ( $this->restricted_widgets() as $restricted ) {
			$this->assertContains( $restricted, $names, "Restriction list names a widget that doesn't exist: {$restricted}" );
		}
	}

	/**
	 * Issue #557: the Logout Link widget gets the restriction controls.
	 */
	public function test_issue_557_logout_link_gets_restriction_controls() {
		$this->assertSame( 'logout', ( new WPUM_LogoutLink() )->get_name() );
		$this->assertContains( 'logout', $this->restricted_widgets() );
	}
}
