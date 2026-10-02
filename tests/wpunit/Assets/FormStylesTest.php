<?php
/**
 * Optional form styles (Users > Settings > General > Misc Settings > Form Styles).
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class FormStylesTest extends WPUMTestCase {

	/**
	 * @var string
	 */
	protected $original_theme;

	public function setUp(): void {
		parent::setUp();
		$this->original_theme = get_stylesheet();
		wpum_delete_option( 'form_styles' );
		wp_dequeue_style( 'wpum-form-styles' );
		wp_deregister_style( 'wpum-form-styles' );
	}

	public function tearDown(): void {
		switch_theme( $this->original_theme );
		wpum_delete_option( 'form_styles' );
		parent::tearDown();
	}

	protected function run_upgrade() {
		$updates = new \ReflectionMethod( 'WPUM_Plugin_Updates', 'upgrade_v2_9_22' );
		$updates->setAccessible( true );
		$updates->invoke( ( new \ReflectionClass( 'WPUM_Plugin_Updates' ) )->newInstanceWithoutConstructor() );
	}

	public function test_stylesheet_loads_when_enabled() {
		wpum_update_option( 'form_styles', true );

		wpum_load_scripts();

		$this->assertTrue( wp_style_is( 'wpum-form-styles', 'enqueued' ) );
	}

	public function test_stylesheet_not_loaded_when_disabled() {
		wpum_load_scripts();

		$this->assertFalse( wp_style_is( 'wpum-form-styles', 'enqueued' ) );
	}

	public function test_built_stylesheet_exists() {
		$this->assertFileExists( WPUM_PLUGIN_DIR . 'assets/css/wpum-form-styles.min.css' );
	}

	public function test_fresh_install_enables_form_styles() {
		$version = get_option( 'wpum_version' );
		delete_option( 'wpum_version' );

		wpum_run_install();

		$this->assertTrue( (bool) wpum_get_option( 'form_styles' ) );
		update_option( 'wpum_version', $version );
	}

	public function test_reactivation_leaves_existing_site_unchanged() {
		update_option( 'wpum_version', '2.9.21' );

		wpum_run_install();

		$this->assertFalse( (bool) wpum_get_option( 'form_styles' ) );
	}

	public function test_upgrade_enables_form_styles_on_block_theme() {
		if ( ! wp_get_theme( 'twentytwentyfive' )->exists() ) {
			$this->markTestSkipped( 'Twenty Twenty-Five is not installed.' );
		}
		switch_theme( 'twentytwentyfive' );

		$this->run_upgrade();

		$this->assertTrue( (bool) wpum_get_option( 'form_styles' ) );
	}

	public function test_upgrade_leaves_classic_theme_unchanged() {
		$classic = null;
		foreach ( wp_get_themes() as $slug => $theme ) {
			if ( ! $theme->is_block_theme() ) {
				$classic = $slug;
				break;
			}
		}
		if ( ! $classic ) {
			$this->markTestSkipped( 'No classic theme installed.' );
		}
		switch_theme( $classic );

		$this->run_upgrade();

		$this->assertFalse( (bool) wpum_get_option( 'form_styles' ) );
	}
}
