<?php

/**
 * Test the functions that update registration forms.
 *
 * The forms editor's ajax handlers call these, so they carry the editor's rules:
 * no administrator role, fields mapped through the field ID filter, settings
 * sanitized by type.
 */
class RegistrationFormServiceTest extends WPUMTestCase {

	/**
	 * @var int
	 */
	protected $form_id;

	public function _setUp() {
		parent::_setUp();

		$this->form_id = WPUM()->registration_forms->insert( array(
			'name' => 'Service Test Form',
		), 'registration_form' );

		wp_cache_flush();
	}

	public function _tearDown() {
		WPUM()->registration_forms->delete( $this->form_id );
		remove_all_filters( 'wpum_save_registration_form_field_id' );
		wp_cache_flush();

		parent::_tearDown();
	}

	/**
	 * Find a default field by its primary ID.
	 *
	 * @param string $primary_id
	 *
	 * @return int
	 */
	protected function get_field_id( $primary_id ) {
		foreach ( WPUM()->fields->get_fields( array( 'number' => -1 ) ) as $field ) {
			if ( $primary_id === $field->get_primary_id() ) {
				return (int) $field->get_ID();
			}
		}

		$this->fail( 'Default field not found: ' . $primary_id );
	}

	/**
	 * @return WPUM_Registration_Form
	 */
	protected function form() {
		return new WPUM_Registration_Form( $this->form_id );
	}

	/*
	 * Name.
	 */

	public function test_update_name() {
		$result = wpum_update_registration_form_name( $this->form_id, 'Members' );

		$this->assertTrue( $result );
		$this->assertSame( 'Members', $this->form()->get_name() );
	}

	public function test_update_name_strips_tags() {
		wpum_update_registration_form_name( $this->form_id, '<b>Members</b> <script>x</script>' );

		$this->assertSame( 'Members', $this->form()->get_name() );
	}

	public function test_update_name_rejects_empty_name() {
		$result = wpum_update_registration_form_name( $this->form_id, '   ' );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_invalid_name', $result->get_error_code() );
		$this->assertSame( 'Service Test Form', $this->form()->get_name() );
	}

	public function test_update_name_rejects_missing_form() {
		$result = wpum_update_registration_form_name( 999999, 'Members' );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_not_found', $result->get_error_code() );
	}

	public function test_update_name_clears_the_forms_list_cache() {
		// Prime the cache used by the forms list.
		WPUM()->registration_forms->get_forms( array( 'number' => -1 ) );

		wpum_update_registration_form_name( $this->form_id, 'Renamed Form' );

		$names = array();
		foreach ( WPUM()->registration_forms->get_forms( array( 'number' => -1 ) ) as $form ) {
			$names[] = $form->get_name();
		}

		$this->assertContains( 'Renamed Form', $names );
	}

	/*
	 * Fields.
	 */

	public function test_update_fields_saves_ids_in_order() {
		$email    = $this->get_field_id( 'user_email' );
		$password = $this->get_field_id( 'user_password' );

		$result = wpum_update_registration_form_fields( $this->form_id, array( $password, $email ) );

		$this->assertTrue( $result );
		$this->assertSame( array( $password, $email ), array_map( 'intval', $this->form()->get_meta( 'fields' ) ) );
	}

	public function test_update_fields_accepts_editor_format() {
		$email = $this->get_field_id( 'user_email' );

		$result = wpum_update_registration_form_fields( $this->form_id, array(
			array(
				'id'   => (string) $email,
				'name' => 'Email',
			),
		) );

		$this->assertTrue( $result );
		$this->assertSame( array( $email ), array_map( 'intval', $this->form()->get_meta( 'fields' ) ) );
	}

	public function test_update_fields_applies_field_id_filter() {
		add_filter( 'wpum_save_registration_form_field_id', function ( $id, $raw_id ) {
			return 0 === strpos( (string) $raw_id, 'addon_' ) ? $raw_id : $id;
		}, 10, 2 );

		wpum_update_registration_form_fields( $this->form_id, array( 'addon_field' ) );

		$this->assertSame( array( 'addon_field' ), $this->form()->get_meta( 'fields' ) );
	}

	public function test_update_fields_rejects_missing_form() {
		$result = wpum_update_registration_form_fields( 999999, array( $this->get_field_id( 'user_email' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_not_found', $result->get_error_code() );
	}

	public function test_update_fields_rejects_empty_list() {
		$result = wpum_update_registration_form_fields( $this->form_id, array() );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_no_fields', $result->get_error_code() );
	}

	public function test_update_fields_strict_rejects_unknown_field() {
		$result = wpum_update_registration_form_fields( $this->form_id, array( 999999 ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_invalid_field', $result->get_error_code() );
		$this->assertEmpty( $this->form()->get_meta( 'fields' ) );
	}

	public function test_update_fields_strict_rejects_duplicate_field() {
		$email = $this->get_field_id( 'user_email' );

		$result = wpum_update_registration_form_fields( $this->form_id, array( $email, $email ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_duplicate_field', $result->get_error_code() );
	}

	public function test_update_fields_strict_rejects_field_not_allowed_in_registration() {
		$result = wpum_update_registration_form_fields( $this->form_id, array( $this->get_field_id( 'user_nickname' ) ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_field_not_allowed', $result->get_error_code() );
	}

	public function test_update_fields_strict_accepts_valid_fields() {
		$email = $this->get_field_id( 'user_email' );

		$this->assertTrue( wpum_update_registration_form_fields( $this->form_id, array( $email ), array( 'strict' => true ) ) );
	}

	public function test_non_allowed_fields_follow_options() {
		$this->assertContains( 'user_nickname', wpum_get_registration_form_non_allowed_fields() );
		$this->assertContains( 'user_displayname', wpum_get_registration_form_non_allowed_fields() );
	}

	/*
	 * Settings.
	 */

	public function test_update_settings_saves_role() {
		$result = wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'editor' ) ) );

		$this->assertTrue( $result );
		$this->assertSame( array( 'editor' ), $this->form()->get_setting( 'role' ) );
		$this->assertSame( 'editor', $this->form()->get_role_key() );
	}

	public function test_update_settings_skips_administrator_role() {
		wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'administrator' ) ) );

		$this->assertEmpty( $this->form()->get_setting( 'role' ) );
	}

	public function test_update_settings_skips_unknown_role() {
		wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'not_a_role' ) ) );

		$this->assertEmpty( $this->form()->get_setting( 'role' ) );
	}

	public function test_update_settings_strict_rejects_administrator_role() {
		$result = wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'administrator' ) ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_invalid_role', $result->get_error_code() );
	}

	public function test_update_settings_strict_checks_every_role() {
		$result = wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'subscriber', 'administrator' ) ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_invalid_role', $result->get_error_code() );
	}

	public function test_update_settings_sanitizes_checkbox() {
		wpum_update_registration_form_settings( $this->form_id, array( 'login_after_registration' => 'true' ) );

		$this->assertNotEmpty( $this->form()->get_setting( 'login_after_registration' ) );
	}

	public function test_update_settings_ignores_unknown_setting() {
		$result = wpum_update_registration_form_settings( $this->form_id, array( 'not_a_setting' => 'x' ) );

		$this->assertTrue( $result );
		$this->assertEmpty( $this->form()->get_meta( 'not_a_setting' ) );
	}

	public function test_update_settings_strict_rejects_unknown_setting_and_saves_nothing() {
		$result = wpum_update_registration_form_settings( $this->form_id, array(
			'role'          => array( 'editor' ),
			'not_a_setting' => 'x',
		), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_unknown_setting', $result->get_error_code() );
		$this->assertEmpty( $this->form()->get_setting( 'role' ) );
	}

	public function test_update_settings_strict_rejects_value_outside_options() {
		$result = wpum_update_registration_form_settings( $this->form_id, array( 'register_roles' => array( 'not_a_role' ) ), array( 'strict' => true ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_invalid_option', $result->get_error_code() );
	}

	public function test_update_settings_strict_accepts_value_in_options() {
		$result = wpum_update_registration_form_settings( $this->form_id, array( 'register_roles' => array( 'subscriber' ) ), array( 'strict' => true ) );

		$this->assertTrue( $result );
		$this->assertSame( array( 'subscriber' ), $this->form()->get_setting( 'register_roles' ) );
	}

	public function test_update_settings_strict_rejects_value_when_options_are_empty() {
		$add_setting = function ( $settings ) {
			$settings[] = array(
				'id'      => 'empty_options_setting',
				'name'    => 'Empty options',
				'type'    => 'multiselect',
				'options' => array(),
			);

			return $settings;
		};
		add_filter( 'wpum_registration_form_settings_options', $add_setting );

		$result = wpum_update_registration_form_settings( $this->form_id, array( 'empty_options_setting' => array( 'anything' ) ), array( 'strict' => true ) );
		$empty  = wpum_update_registration_form_settings( $this->form_id, array( 'empty_options_setting' => array() ), array(
			'strict'  => true,
			'partial' => true,
		) );

		remove_filter( 'wpum_registration_form_settings_options', $add_setting );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_invalid_option', $result->get_error_code() );
		$this->assertTrue( $empty, 'An empty selection is still valid.' );
	}

	public function test_full_update_deletes_settings_not_sent() {
		wpum_update_registration_form_settings( $this->form_id, array(
			'role'                     => array( 'editor' ),
			'login_after_registration' => 'true',
		) );

		wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'editor' ) ) );

		$this->assertSame( array( 'editor' ), $this->form()->get_setting( 'role' ) );
		$this->assertEmpty( $this->form()->get_setting( 'login_after_registration' ) );
	}

	public function test_partial_update_keeps_settings_not_sent() {
		wpum_update_registration_form_settings( $this->form_id, array(
			'role'                     => array( 'editor' ),
			'login_after_registration' => 'true',
		) );

		wpum_update_registration_form_settings( $this->form_id, array( 'role' => array( 'author' ) ), array( 'partial' => true ) );

		$this->assertSame( array( 'author' ), $this->form()->get_setting( 'role' ) );
		$this->assertNotEmpty( $this->form()->get_setting( 'login_after_registration' ) );
	}

	public function test_update_settings_rejects_missing_form() {
		$result = wpum_update_registration_form_settings( 999999, array( 'role' => array( 'editor' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_registration_form_not_found', $result->get_error_code() );
	}

	/*
	 * Sanitizers, which must be registered outside the admin too.
	 */

	public function test_settings_sanitizers_are_registered() {
		$this->assertSame( 'Text', apply_filters( 'wpum_form_settings_sanitize_text', ' <i>Text</i> ' ) );
		$this->assertTrue( apply_filters( 'wpum_form_settings_sanitize_checkbox', '1' ) );
		$this->assertFalse( apply_filters( 'wpum_form_settings_sanitize_checkbox', 'false' ) );
		$this->assertSame( array( 'a', 'b' ), apply_filters( 'wpum_form_settings_sanitize_multiselect', 'a,b' ) );
	}

	public function test_setting_option_values_handles_both_formats() {
		$this->assertSame( array( 'a', 'b' ), wpum_get_setting_option_values( array(
			'a' => 'A',
			'b' => 'B',
		) ) );
		$this->assertSame( array( '12' ), wpum_get_setting_option_values( array(
			array(
				'value' => 12,
				'label' => 'Twelve',
			),
		) ) );
	}
}
