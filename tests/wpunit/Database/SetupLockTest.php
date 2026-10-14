<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * The first-run setup must only run once, even when requests overlap.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/136
 */
class SetupLockTest extends WPUMTestCase {

	public function _setUp() {
		parent::_setUp();
		wpum_release_setup_lock();
	}

	public function _tearDown() {
		wpum_release_setup_lock();
		remove_all_filters( 'pre_option_wpum_setup_is_complete' );
		update_option( 'wpum_setup_is_complete', true );
		parent::_tearDown();
	}

	/**
	 * Mark setup as not yet run, as on a fresh install.
	 */
	private function reset_setup_flags() {
		delete_option( 'wpum_setup_is_complete' );
		delete_option( 'wpum_version_upgraded_from' );
	}

	private function count_registration_forms() {
		global $wpdb;

		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wpum_registration_forms" );
	}

	public function test_only_one_request_gets_the_lock() {
		$this->assertTrue( wpum_create_setup_lock() );
		$this->assertFalse( wpum_create_setup_lock() );

		wpum_release_setup_lock();

		$this->assertTrue( wpum_create_setup_lock() );
	}

	public function test_stale_lock_is_taken_over() {
		global $wpdb;

		$wpdb->insert( $wpdb->options, array(
			'option_name'  => 'wpum_setup_lock',
			'option_value' => time() - 600,
			'autoload'     => 'no',
		) );

		$this->assertTrue( wpum_create_setup_lock() );
		$this->assertFalse( wpum_create_setup_lock() );
	}

	public function test_setup_does_not_run_while_another_request_holds_the_lock() {
		$this->reset_setup_flags();
		$forms_before = $this->count_registration_forms();

		wpum_create_setup_lock();
		wpum_complete_setup();

		$this->assertSame( $forms_before, $this->count_registration_forms() );
		$this->assertFalse( (bool) get_option( 'wpum_setup_is_complete' ) );
	}

	public function test_setup_does_not_rerun_when_the_options_cache_is_stale() {
		// Another request has already completed the setup in the database, but
		// this request loaded its options before that happened.
		update_option( 'wpum_setup_is_complete', true );
		delete_option( 'wpum_version_upgraded_from' );
		add_filter( 'pre_option_wpum_setup_is_complete', '__return_false' );

		$forms_before = $this->count_registration_forms();

		wpum_complete_setup();

		$this->assertSame( $forms_before, $this->count_registration_forms() );
		$this->assertTrue( wpum_create_setup_lock(), 'The lock should be released.' );
	}

	public function test_setup_runs_once_and_releases_the_lock() {
		$this->reset_setup_flags();
		$forms_before = $this->count_registration_forms();

		wpum_complete_setup();
		wpum_complete_setup();

		$this->assertSame( $forms_before + 1, $this->count_registration_forms() );
		$this->assertTrue( (bool) get_option( 'wpum_setup_is_complete' ) );
		$this->assertTrue( wpum_create_setup_lock(), 'The lock should be released.' );
	}
}
