<?php
/**
 * Callers of get_forms() that need every registration form.
 *
 * WPUM_DB_Registration_Forms::get_forms() defaults to 20 rows. Callers that
 * build a list of all forms, or search all forms, must ask for every row.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/464
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class GetFormsCallersTest extends WPUMTestCase {

	/**
	 * Number of forms created, comfortably above the default limit of 20.
	 */
	const FORMS = 25;

	/**
	 * IDs of the forms created for the test, oldest first.
	 *
	 * @var array
	 */
	private $form_ids = array();

	/**
	 * IDs of the forms that were the default before the test.
	 *
	 * @var array
	 */
	private $original_defaults = array();

	/**
	 * Filter adding a fake registration setting, removed in tear down.
	 *
	 * @var callable|null
	 */
	private $settings_filter;

	public function _setUp() {
		parent::_setUp();

		foreach ( WPUM()->registration_forms->get_forms( array( 'number' => -1 ) ) as $form ) {
			if ( $form->is_default() ) {
				$this->original_defaults[] = $form->get_ID();
			}
		}

		for ( $i = 1; $i <= self::FORMS; $i++ ) {
			$this->form_ids[] = WPUM()->registration_forms->insert(
				array(
					'name' => 'Issue 464 Form ' . $i,
				),
				'registration_form'
			);
		}

		wp_cache_flush();
	}

	public function _tearDown() {
		foreach ( $this->form_ids as $form_id ) {
			WPUM()->registration_forms->delete( $form_id );
		}

		foreach ( $this->original_defaults as $form_id ) {
			( new WPUM_Registration_Form( $form_id ) )->update_meta( 'default', true );
		}

		if ( $this->settings_filter ) {
			remove_filter( 'wpum_registered_settings', $this->settings_filter );
		}
		wpum_delete_option( 'wpum_issue_464_setting' );

		wp_cache_flush();

		parent::_tearDown();
	}

	/**
	 * Issue #464: the ACF settings metabox lists every registration form, not
	 * just the first 20.
	 */
	public function test_issue_464_acf_metabox_lists_every_form() {
		require_once WPUM_PLUGIN_DIR . 'includes/admin/class-wpum-addon-acf.php';

		ob_start();
		( new WPUM_Addon_ACF() )->setting_metabox();
		$html = ob_get_clean();

		// Other tests may leave forms behind, so check every form this test
		// created rather than assuming where they fall in the list.
		for ( $i = 1; $i <= self::FORMS; $i++ ) {
			// Whitespace then the closing tag, so Form 1 can't match Form 10.
			$this->assertSame( 1, preg_match( '/Issue 464 Form ' . $i . '\s*</', $html ), "Form $i is missing from the list." );
		}
	}

	/**
	 * Issue #464: the 2.2 upgrade finds the default registration form even
	 * when it isn't in the first 20, and moves the registration settings onto
	 * it.
	 */
	public function test_issue_464_v2_2_upgrade_finds_a_default_form_past_the_first_20() {
		foreach ( $this->original_defaults as $form_id ) {
			( new WPUM_Registration_Form( $form_id ) )->update_meta( 'default', false );
		}

		$default_id = end( $this->form_ids );
		( new WPUM_Registration_Form( $default_id ) )->update_meta( 'default', true );
		wp_cache_flush();

		$this->settings_filter = function ( $settings ) {
			$settings['registration'][] = array( 'id' => 'wpum_issue_464_setting' );

			return $settings;
		};
		add_filter( 'wpum_registered_settings', $this->settings_filter );
		wpum_update_option( 'wpum_issue_464_setting', 'moved' );

		$updates = ( new ReflectionClass( 'WPUM_Plugin_Updates' ) )->newInstanceWithoutConstructor();
		$upgrade = new ReflectionMethod( 'WPUM_Plugin_Updates', 'upgrade_v2_2' );
		$upgrade->setAccessible( true );
		$upgrade->invoke( $updates );

		wp_cache_flush();

		$this->assertSame( 'moved', ( new WPUM_Registration_Form( $default_id ) )->get_meta( 'wpum_issue_464_setting' ) );
		$this->assertFalse( wpum_get_option( 'wpum_issue_464_setting' ) );
	}
}
