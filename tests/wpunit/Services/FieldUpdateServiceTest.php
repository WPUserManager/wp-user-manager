<?php

/**
 * Test the function that updates a field's settings.
 *
 * The fields editor's ajax handler calls it with the settings of the tab being
 * saved. Called without them, it uses every setting of the field's type.
 */
class FieldUpdateServiceTest extends WPUMTestCase {

	/**
	 * Fields created by a test.
	 *
	 * @var array
	 */
	protected $field_ids = array();

	public function _tearDown() {
		foreach ( $this->field_ids as $field_id ) {
			WPUM()->fields->delete( $field_id );
		}

		$this->field_ids = array();
		wp_cache_flush();

		parent::_tearDown();
	}

	/**
	 * Create a field in the primary group.
	 *
	 * @param string $type
	 * @param array  $metas
	 *
	 * @return int
	 */
	protected function create_field( $type = 'text', $metas = array() ) {
		$field_id = WPUM()->fields->insert( array(
			'group_id'    => 1,
			'type'        => $type,
			'name'        => 'Service Test Field',
			'description' => '',
			'field_order' => 99,
		) );

		$field = new WPUM_Field( $field_id );
		foreach ( $metas as $key => $value ) {
			$field->add_meta( $key, $value );
		}

		$this->field_ids[] = $field_id;

		return $field_id;
	}

	public function test_registered_field_type_lookup() {
		$text = wpum_get_registered_field_type( 'text' );

		$this->assertIsArray( $text );
		$this->assertSame( 'text', $text['type'] );
		$this->assertArrayHasKey( 'settings', $text );
		$this->assertFalse( wpum_get_registered_field_type( 'not_a_field_type' ) );
	}

	public function test_settings_config_covers_every_tab() {
		$config = wpum_get_field_settings_config( new WPUM_Field( $this->create_field() ) );

		foreach ( array( 'field_title', 'user_meta_key', 'placeholder', 'required', 'visibility', 'editing', 'roles' ) as $key ) {
			$this->assertArrayHasKey( $key, $config, $key . ' should be in the settings config.' );
		}
	}

	public function test_settings_config_leaves_out_meta_key_for_primary_fields() {
		$config = wpum_get_field_settings_config( new WPUM_Field( $this->create_field( 'user_firstname' ) ) );

		$this->assertArrayNotHasKey( 'user_meta_key', $config );
		$this->assertArrayHasKey( 'placeholder', $config );
	}

	public function test_updates_title_and_description() {
		$field_id = $this->create_field();

		$result = wpum_update_field_settings( $field_id, array(
			'field_title'       => '<b>Job title</b>',
			'field_description' => 'Your <em>role</em><script>x</script>',
		) );

		$field = new WPUM_Field( $field_id );
		$this->assertTrue( $result );
		$this->assertSame( 'Job title', $field->get_name() );
		// The field model strips tags from the description column when it saves.
		$this->assertSame( 'Your rolex', $field->get_description() );
	}

	public function test_meta_key_gets_wpum_prefix() {
		$field_id = $this->create_field();

		wpum_update_field_settings( $field_id, array( 'user_meta_key' => 'Job Title!' ) );

		$this->assertSame( 'wpum_jobtitle', ( new WPUM_Field( $field_id ) )->get_meta( 'user_meta_key' ) );
	}

	public function test_meta_key_with_prefix_is_kept() {
		$field_id = $this->create_field();

		wpum_update_field_settings( $field_id, array( 'user_meta_key' => 'wpum_job_title' ) );

		$this->assertSame( 'wpum_job_title', ( new WPUM_Field( $field_id ) )->get_meta( 'user_meta_key' ) );
	}

	public function test_file_field_meta_key_gets_file_prefix() {
		$field_id = $this->create_field( 'file' );

		wpum_update_field_settings( $field_id, array( 'user_meta_key' => 'cv' ) );

		$this->assertSame( 'wpum_file_field_cv', ( new WPUM_Field( $field_id ) )->get_meta( 'user_meta_key' ) );
	}

	public function test_primary_field_meta_key_is_not_changed() {
		$field_id = $this->create_field( 'user_firstname', array( 'user_meta_key' => 'firstname' ) );

		wpum_update_field_settings( $field_id, array( 'user_meta_key' => 'something_else' ) );

		$this->assertSame( 'firstname', ( new WPUM_Field( $field_id ) )->get_meta( 'user_meta_key' ) );
	}

	public function test_settings_are_sanitized_by_type() {
		$field_id = $this->create_field();

		wpum_update_field_settings( $field_id, array(
			'required'    => true,
			'visibility'  => 'hidden',
			'placeholder' => '<b>e.g.</b> Developer',
		) );

		$field = new WPUM_Field( $field_id );
		$this->assertNotEmpty( $field->get_meta( 'required' ) );
		$this->assertSame( 'hidden', $field->get_meta( 'visibility' ) );
		$this->assertSame( 'e.g. Developer', $field->get_meta( 'placeholder' ) );
	}

	public function test_editor_checkbox_value_is_a_string() {
		$field_id = $this->create_field();

		wpum_update_field_settings( $field_id, array( 'required' => 'true' ) );
		$this->assertNotEmpty( ( new WPUM_Field( $field_id ) )->get_meta( 'required' ) );

		wpum_update_field_settings( $field_id, array( 'required' => 'false' ) );
		$this->assertEmpty( ( new WPUM_Field( $field_id ) )->get_meta( 'required' ), 'An unchecked box deletes the setting.' );
	}

	public function test_only_settings_given_are_sanitized_and_saved() {
		$field_id = $this->create_field();

		// The editor sends the settings of the tab being saved.
		wpum_update_field_settings( $field_id, array(
			'required'   => 'true',
			'visibility' => 'hidden',
		), array(
			'settings' => array(
				'required' => array(
					'type'  => 'checkbox',
					'model' => 'required',
				),
			),
		) );

		$field = new WPUM_Field( $field_id );
		$this->assertNotEmpty( $field->get_meta( 'required' ) );
		$this->assertEmpty( $field->get_meta( 'visibility' ), 'A setting outside the tab is skipped.' );
	}

	public function test_unknown_setting_is_skipped() {
		$field_id = $this->create_field();

		$this->assertTrue( wpum_update_field_settings( $field_id, array( 'not_a_setting' => 'x' ) ) );
		$this->assertEmpty( ( new WPUM_Field( $field_id ) )->get_meta( 'not_a_setting' ) );
	}

	public function test_dropdown_options_are_saved() {
		$field_id = $this->create_field( 'dropdown' );

		wpum_update_field_settings( $field_id, array(), array(
			'dropdown_options' => array(
				array(
					'value' => 'uk',
					'label' => '<b>United Kingdom</b>',
				),
				array(
					'value' => 'fr',
					'label' => 'France',
				),
			),
		) );

		$this->assertSame( array(
			array(
				'value' => 'uk',
				'label' => 'United Kingdom',
			),
			array(
				'value' => 'fr',
				'label' => 'France',
			),
		), ( new WPUM_Field( $field_id ) )->get_meta( 'dropdown_options' ) );
	}

	public function test_missing_field_returns_error() {
		$result = wpum_update_field_settings( 999999, array( 'field_title' => 'X' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_not_found', $result->get_error_code() );
	}

	public function test_strict_rejects_unknown_setting_and_saves_nothing() {
		$field_id = $this->create_field();

		$result = wpum_update_field_settings( $field_id, array(
			'field_title'   => 'Changed',
			'not_a_setting' => 'x',
		), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_unknown_setting', $result->get_error_code() );
		$this->assertSame( 'Service Test Field', ( new WPUM_Field( $field_id ) )->get_name() );
	}

	public function test_strict_rejects_excluded_setting() {
		$result = wpum_update_field_settings( $this->create_field(), array( 'conditions' => array() ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_unknown_setting', $result->get_error_code() );
	}

	public function test_strict_rejects_meta_key_on_primary_field() {
		$result = wpum_update_field_settings( $this->create_field( 'user_firstname' ), array( 'user_meta_key' => 'x' ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_unknown_setting', $result->get_error_code() );
	}

	public function test_strict_rejects_empty_title() {
		$result = wpum_update_field_settings( $this->create_field(), array( 'field_title' => '<b></b>' ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_invalid_title', $result->get_error_code() );
	}

	public function test_strict_rejects_meta_key_used_by_another_field() {
		$this->create_field( 'text', array( 'user_meta_key' => 'wpum_company' ) );
		$field_id = $this->create_field();

		$result = wpum_update_field_settings( $field_id, array( 'user_meta_key' => 'company' ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_duplicate_meta_key', $result->get_error_code() );
	}

	public function test_strict_allows_field_to_keep_its_own_meta_key() {
		$field_id = $this->create_field( 'text', array( 'user_meta_key' => 'wpum_company' ) );

		$this->assertTrue( wpum_update_field_settings( $field_id, array( 'user_meta_key' => 'company' ), array( 'strict' => true ) ) );
	}

	public function test_strict_rejects_value_outside_options() {
		$result = wpum_update_field_settings( $this->create_field(), array( 'visibility' => 'everyone' ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_field_invalid_option', $result->get_error_code() );
	}

	public function test_strict_accepts_valid_settings() {
		$field_id = $this->create_field();

		$result = wpum_update_field_settings( $field_id, array(
			'field_title'   => 'Company',
			'user_meta_key' => 'company_name',
			'visibility'    => 'public',
			'roles'         => array( 'subscriber' ),
		), array( 'strict' => true ) );

		$field = new WPUM_Field( $field_id );
		$this->assertTrue( $result );
		$this->assertSame( 'Company', $field->get_name() );
		$this->assertSame( 'wpum_company_name', $field->get_meta( 'user_meta_key' ) );
		$this->assertSame( array( 'subscriber' ), $field->get_meta( 'roles' ) );
	}

	public function test_meta_key_exists_check() {
		$field_id = $this->create_field( 'text', array( 'user_meta_key' => 'wpum_unique_check' ) );

		$this->assertTrue( wpum_field_user_meta_key_exists( 'wpum_unique_check' ) );
		$this->assertFalse( wpum_field_user_meta_key_exists( 'wpum_unique_check', $field_id ) );
		$this->assertFalse( wpum_field_user_meta_key_exists( 'wpum_nobody_uses_this' ) );
	}
}
