<?php

/**
 * Test the registration forms list ajax endpoint.
 *
 * The forms list in the admin is a Vue app with no pagination, so the endpoint
 * has to return every form. The database layer defaults to 20 rows, which
 * silently hid any form after the twentieth.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/451
 */
class RegistrationFormsListTest extends \Codeception\TestCase\WPAjaxTestCase {

	/**
	 * Number of extra forms created, comfortably above the default limit of 20.
	 */
	const EXTRA_FORMS = 25;

	/**
	 * IDs of the forms created for the test.
	 *
	 * @var array
	 */
	protected $form_ids = array();

	public function _setUp() {
		parent::_setUp();

		if ( ! function_exists( 'WPUM' ) ) {
			$this->markTestSkipped( 'WPUM plugin is not active.' );
		}

		// The ajax handler requires an admin context.
		set_current_screen( 'edit.php' );

		$this->ensure_tables();

		$this->form_ids = array();

		for ( $i = 0; $i < self::EXTRA_FORMS; $i ++ ) {
			$this->form_ids[] = WPUM()->registration_forms->insert( array(
				'name' => 'Issue 451 Form ' . $i,
			), 'registration_form' );
		}
	}

	public function _tearDown() {
		foreach ( $this->form_ids as $form_id ) {
			WPUM()->registration_forms->delete( $form_id );
		}

		wp_cache_flush();

		parent::_tearDown();
	}

	/**
	 * Ensure the WPUM custom tables exist.
	 */
	protected function ensure_tables() {
		$tables = array(
			new \WPUM_DB_Table_Registration_Forms(),
			new \WPUM_DB_Table_Registration_Forms_Meta(),
		);

		foreach ( $tables as $table ) {
			if ( ! $table->exists() ) {
				$table->create();
			}
		}
	}

	/**
	 * The database layer still defaults to 20 rows, and `number` below 1 means
	 * "no limit". This documents the behaviour the ajax endpoint relies on.
	 */
	public function test_issue_451_database_default_limits_to_twenty() {
		$default = WPUM()->registration_forms->get_forms();
		$all     = WPUM()->registration_forms->get_forms( array(
			'number' => -1,
		) );

		$this->assertCount( 20, $default, 'The database layer should still default to 20 forms.' );
		$this->assertGreaterThanOrEqual( self::EXTRA_FORMS, count( $all ), 'A number below 1 should return every form.' );
	}

	/**
	 * The ajax endpoint used by the forms list must return every form, not just
	 * the first 20.
	 */
	public function test_issue_451_ajax_returns_all_forms() {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		// The editor is only loaded in an admin request, so it is not available during the test bootstrap.
		require_once WPUM_PLUGIN_DIR . 'includes/forms/class-wpum-registration-forms-editor.php';

		$editor = new WPUM_Registration_Forms_Editor();

		$_POST['nonce']    = wp_create_nonce( 'wpum_get_registration_forms' );
		$_REQUEST['nonce'] = $_POST['nonce'];

		ob_start();

		try {
			$editor->get_forms();
		} catch ( \WPAjaxDieContinueException $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			// Expected: wp_send_json_success() ends the request.
		}

		$response = json_decode( $this->_last_response );

		$this->assertTrue( $response->success, 'The ajax request should succeed.' );
		$this->assertGreaterThanOrEqual( self::EXTRA_FORMS, count( $response->data ), 'Every form should be returned, not just the first 20.' );

		$returned_ids = wp_list_pluck( $response->data, 'id' );

		foreach ( $this->form_ids as $form_id ) {
			$this->assertContains( (int) $form_id, array_map( 'intval', $returned_ids ), 'Form ' . $form_id . ' should be in the list.' );
		}
	}
}
