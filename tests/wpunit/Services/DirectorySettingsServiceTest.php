<?php

/**
 * Test the functions that create user directories and read and change their
 * settings outside the directory edit screen.
 */
class DirectorySettingsServiceTest extends WPUMTestCase {

	/**
	 * @return int
	 */
	protected function count_directories() {
		return count( get_posts( array(
			'post_type'   => 'wpum_directory',
			'post_status' => 'any',
			'numberposts' => -1,
			'fields'      => 'ids',
		) ) );
	}

	public function test_settings_fields_are_listed_by_name() {
		$fields = wpum_get_directory_settings_fields();

		foreach ( array( 'directory_assigned_roles', 'directory_excluded_users', 'directory_profiles_per_page', 'directory_search_form', 'directory_search_fields', 'directory_display_sorter', 'directory_display_amount_filter', 'directory_sorting_method', 'directory_template', 'directory_user_template' ) as $name ) {
			$this->assertArrayHasKey( $name, $fields );
		}
	}

	public function test_create_directory_with_settings() {
		$directory_id = wpum_create_directory( 'Members', array(
			'directory_assigned_roles'    => array( 'subscriber' ),
			'directory_search_form'       => true,
			'directory_search_fields'     => array( 'first_name' ),
			'directory_sorting_method'    => 'oldest',
			'directory_profiles_per_page' => 12,
			'directory_excluded_users'    => '1,6',
		) );

		$this->assertIsInt( $directory_id );
		$this->assertSame( 'wpum_directory', get_post_type( $directory_id ) );
		$this->assertSame( 'publish', get_post_status( $directory_id ) );
		$this->assertSame( 'Members', get_the_title( $directory_id ) );

		$settings = wpum_get_directory_settings( $directory_id );

		$this->assertSame( array( 'subscriber' ), $settings['directory_assigned_roles'] );
		$this->assertTrue( $settings['directory_search_form'] );
		$this->assertSame( array( 'first_name' ), $settings['directory_search_fields'] );
		$this->assertSame( 'oldest', $settings['directory_sorting_method'] );
		$this->assertSame( '12', $settings['directory_profiles_per_page'] );
		$this->assertSame( '1, 6', $settings['directory_excluded_users'] );
		$this->assertFalse( $settings['directory_display_sorter'] );
	}

	public function test_settings_are_read_by_the_directory_shortcode() {
		$directory_id = wpum_create_directory( 'Members', array( 'directory_sorting_method' => 'last_name' ) );

		// The shortcode reads the same Carbon Fields meta.
		WPUM()->directories_editor->register_directory_settings();
		$this->assertSame( 'last_name', \WPUM\carbon_get_post_meta( $directory_id, 'directory_sorting_method' ) );
	}

	public function test_new_directory_has_default_search_fields() {
		$directory_id = wpum_create_directory( 'Defaults' );
		$settings     = wpum_get_directory_settings( $directory_id );

		$this->assertSame( array( 'first_name', 'last_name' ), $settings['directory_search_fields'] );
	}

	public function test_create_rejects_empty_title() {
		$result = wpum_create_directory( '  ' );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_directory_invalid_title', $result->get_error_code() );
	}

	public function test_create_with_invalid_settings_creates_nothing() {
		$before = $this->count_directories();

		$result = wpum_create_directory( 'Members', array( 'directory_sorting_method' => 'random' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_directory_invalid_setting', $result->get_error_code() );
		$this->assertSame( $before, $this->count_directories() );
	}

	public function test_update_only_changes_given_settings() {
		$directory_id = wpum_create_directory( 'Members', array(
			'directory_sorting_method' => 'oldest',
			'directory_search_form'    => true,
		) );

		$result = wpum_update_directory_settings( $directory_id, array( 'directory_sorting_method' => 'name' ) );

		$settings = wpum_get_directory_settings( $directory_id );
		$this->assertTrue( $result );
		$this->assertSame( 'name', $settings['directory_sorting_method'] );
		$this->assertTrue( $settings['directory_search_form'] );
	}

	public function test_checkbox_can_be_turned_off() {
		$directory_id = wpum_create_directory( 'Members', array( 'directory_display_sorter' => 'yes' ) );

		wpum_update_directory_settings( $directory_id, array( 'directory_display_sorter' => false ) );

		$this->assertFalse( wpum_get_directory_settings( $directory_id )['directory_display_sorter'] );
	}

	public function test_multiselect_accepts_comma_separated_string() {
		$directory_id = wpum_create_directory( 'Members' );

		wpum_update_directory_settings( $directory_id, array( 'directory_assigned_roles' => 'subscriber, editor' ) );

		$this->assertSame( array( 'subscriber', 'editor' ), wpum_get_directory_settings( $directory_id )['directory_assigned_roles'] );
	}

	public function test_update_with_one_invalid_setting_saves_nothing() {
		$directory_id = wpum_create_directory( 'Members', array( 'directory_sorting_method' => 'oldest' ) );

		$result = wpum_update_directory_settings( $directory_id, array(
			'directory_sorting_method' => 'name',
			'not_a_setting'            => 'x',
		) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_directory_unknown_setting', $result->get_error_code() );
		$this->assertSame( 'oldest', wpum_get_directory_settings( $directory_id )['directory_sorting_method'] );
	}

	/**
	 * @dataProvider invalid_settings
	 *
	 * @param string $name
	 * @param mixed  $value
	 */
	public function test_invalid_values_are_rejected( $name, $value ) {
		$directory_id = wpum_create_directory( 'Members' );

		$result = wpum_update_directory_settings( $directory_id, array( $name => $value ) );

		$this->assertWPError( $result );
		$this->assertSame( 'wpum_directory_invalid_setting', $result->get_error_code() );
	}

	/**
	 * @return array
	 */
	public function invalid_settings() {
		return array(
			'unknown role'          => array( 'directory_assigned_roles', array( 'not_a_role' ) ),
			'unknown search field'  => array( 'directory_search_fields', array( 'user_pass' ) ),
			'unknown sorting'       => array( 'directory_sorting_method', 'random' ),
			'unknown template'      => array( 'directory_template', 'missing-template' ),
			'zero per page'         => array( 'directory_profiles_per_page', 0 ),
			'words per page'        => array( 'directory_profiles_per_page', 'ten' ),
			'excluded user names'   => array( 'directory_excluded_users', 'admin, 6' ),
			'checkbox with a word'  => array( 'directory_search_form', 'maybe' ),
			'array for a select'    => array( 'directory_sorting_method', array( 'name' ) ),
		);
	}

	public function test_not_a_directory_returns_error() {
		$page_id = $this->factory()->post->create( array( 'post_type' => 'page' ) );

		$result = wpum_update_directory_settings( $page_id, array( 'directory_sorting_method' => 'name' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'wpum_directory_not_found', $result->get_error_code() );

		$result = wpum_get_directory_settings( 999999 );
		$this->assertWPError( $result );
		$this->assertSame( 'wpum_directory_not_found', $result->get_error_code() );
	}
}
