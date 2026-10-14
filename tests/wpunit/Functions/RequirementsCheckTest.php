<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * Admins are warned when a required PHP extension is missing.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/77
 */
class RequirementsCheckTest extends WPUMTestCase {

	public function _tearDown() {
		remove_all_filters( 'wpum_required_php_extensions' );
		parent::_tearDown();
	}

	private function require_fake_extension() {
		add_filter( 'wpum_required_php_extensions', function ( $extensions ) {
			$extensions[] = 'wpum_fake_extension';

			return $extensions;
		} );
	}

	private function notice_output() {
		ob_start();
		WPUM()->missing_php_extensions_notice();

		return ob_get_clean();
	}

	public function test_mbstring_is_required() {
		$required = apply_filters( 'wpum_required_php_extensions', array( 'mbstring' ) );

		$this->assertContains( 'mbstring', $required );
		$this->assertNotFalse( has_action( 'admin_notices', array( WPUM(), 'missing_php_extensions_notice' ) ) );
	}

	public function test_no_notice_when_extensions_are_present() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertSame( array(), WP_User_Manager::get_missing_php_extensions() );
		$this->assertSame( '', $this->notice_output() );
	}

	public function test_admin_sees_notice_for_missing_extension() {
		$this->require_fake_extension();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertSame( array( 'wpum_fake_extension' ), WP_User_Manager::get_missing_php_extensions() );
		$this->assertStringContainsString( '<code>wpum_fake_extension</code>', $this->notice_output() );
	}

	public function test_non_admin_sees_no_notice() {
		$this->require_fake_extension();
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( '', $this->notice_output() );
	}
}
