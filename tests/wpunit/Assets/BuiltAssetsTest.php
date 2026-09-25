<?php
/**
 * Guards against unresolved at-rules shipping in the built CSS.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class BuiltAssetsTest extends WPUMTestCase {

	/**
	 * Built stylesheets that ship to the browser.
	 *
	 * @return array<string, array<string>>
	 */
	public function built_stylesheets() {
		return array(
			'wpum.css'     => array( 'assets/css/wpum.css' ),
			'wpum.min.css' => array( 'assets/css/wpum.min.css' ),
		);
	}

	/**
	 * The CSS build is Sass only (`grunt css` = sass -> cssmin), with no PostCSS
	 * step. PostCSS-only syntax such as `@custom-media` therefore passes straight
	 * through into the built file, where browsers silently ignore it — which is
	 * how every responsive rule above the xs breakpoint came to be dead in
	 * shipped releases (issue #76).
	 *
	 * @dataProvider built_stylesheets
	 *
	 * @param string $relative_path Path to the stylesheet, relative to the plugin root.
	 */
	public function test_built_css_has_no_unresolved_at_rules( $relative_path ) {
		$path = dirname( __DIR__, 3 ) . '/' . $relative_path;

		$this->assertFileExists( $path, "Built stylesheet {$relative_path} is missing." );

		$css = file_get_contents( $path );

		$this->assertStringNotContainsString(
			'@custom-media',
			$css,
			"{$relative_path} contains a PostCSS @custom-media declaration. Nothing in the "
			. 'build resolves it, so it ships to the browser and is ignored. Use a Sass '
			. 'variable instead.'
		);

		$this->assertSame(
			0,
			preg_match( '/@media\s*\(\s*--/', $css ),
			"{$relative_path} contains a media query referencing a custom-media name "
			. '(e.g. `@media (--sm-viewport)`). That is invalid CSS to a browser, so every '
			. 'rule inside the block is dead. Use a Sass variable instead.'
		);
	}
}
