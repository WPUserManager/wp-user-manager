<?php
/**
 * The background routine that activates addon licenses saved on 2.9.14 - 2.9.19.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class LicenseReactivationTest extends WPUMTestCase {

	/**
	 * EDD actions requested, in order.
	 *
	 * @var string[]
	 */
	protected $requests = array();

	/**
	 * Status EDD returns for check_license.
	 *
	 * @var string
	 */
	protected $check_status = '';

	public function setUp(): void {
		parent::setUp();
		$this->requests = array();
		update_option( '_wpum_test_addon_license_key', 'test-key' );
		delete_site_transient( 'wpum_test_addon_license_data' );
		add_filter( 'pre_http_request', array( $this, 'fake_edd' ), 10, 3 );
	}

	public function tearDown(): void {
		remove_filter( 'pre_http_request', array( $this, 'fake_edd' ), 10 );
		delete_option( '_wpum_test_addon_license_key' );
		delete_site_transient( 'wpum_test_addon_license_data' );
		parent::tearDown();
	}

	public function fake_edd( $preempt, $args, $url ) {
		if ( 'https://wpusermanager.com' !== $url ) {
			return $preempt;
		}

		$action           = $args['body']['edd_action'];
		$this->requests[] = $action;

		$status = 'activate_license' === $action ? 'valid' : $this->check_status;

		return array(
			'headers'  => array(),
			'body'     => wp_json_encode( array(
				'success' => true,
				'license' => $status,
				'expires' => '2027-03-01 23:59:59',
			) ),
			'response' => array(
				'code'    => 200,
				'message' => 'OK',
			),
			'cookies'  => array(),
		);
	}

	protected function run_repair() {
		$license = new WPUM_License( WPUM_PLUGIN_FILE, 'Test Addon', '123', '1.0.0', 'WP User Manager' );
		$license->maybe_activate_site_inactive_license();

		return get_site_transient( 'wpum_test_addon_license_data' );
	}

	/**
	 * @dataProvider activatable_statuses
	 */
	public function test_activates_key_not_active_on_this_site( $status ) {
		$this->check_status = $status;

		$data = $this->run_repair();

		$this->assertSame( array( 'check_license', 'activate_license' ), $this->requests );
		$this->assertSame( 'valid', $data['status'] );
	}

	public function activatable_statuses() {
		return array(
			'never activated anywhere' => array( 'inactive' ),
			'active on another site'   => array( 'site_inactive' ),
		);
	}

	/**
	 * @dataProvider skipped_statuses
	 */
	public function test_leaves_other_statuses_alone( $status ) {
		$this->check_status = $status;

		$data = $this->run_repair();

		$this->assertSame( array( 'check_license' ), $this->requests );
		$this->assertSame( $status, $data['status'] );
	}

	public function skipped_statuses() {
		return array(
			'valid'    => array( 'valid' ),
			'expired'  => array( 'expired' ),
			'disabled' => array( 'disabled' ),
			'invalid'  => array( 'invalid' ),
		);
	}

	public function test_upgrade_schedules_repair() {
		wp_clear_scheduled_hook( 'wpum_reactivate_inactive_licenses' );

		$upgrade = new ReflectionMethod( 'WPUM_Plugin_Updates', 'upgrade_v2_9_26' );
		$upgrade->setAccessible( true );
		$upgrade->invoke( ( new ReflectionClass( 'WPUM_Plugin_Updates' ) )->newInstanceWithoutConstructor() );

		$this->assertNotFalse( wp_next_scheduled( 'wpum_reactivate_inactive_licenses' ) );

		wp_clear_scheduled_hook( 'wpum_reactivate_inactive_licenses' );
	}
}
