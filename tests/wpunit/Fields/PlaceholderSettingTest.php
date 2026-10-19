<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * The Placeholder setting only appears for field types whose template shows it.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/80
 */
class PlaceholderSettingTest extends WPUMTestCase {

	/**
	 * Get the editor settings a field type registers.
	 *
	 * @param WPUM_Field_Type $field_type
	 *
	 * @return array
	 */
	private function settings_for( $field_type ) {
		$registered = $field_type->register_field_type( array() );
		$group      = reset( $registered );

		return $group['fields'][0]['settings'];
	}

	public function types_without_placeholder() {
		return array(
			'dropdown'      => array( 'WPUM_Field_Dropdown' ),
			'radio'         => array( 'WPUM_Field_Radio' ),
			'checkbox'      => array( 'WPUM_Field_Checkbox' ),
			'multicheckbox' => array( 'WPUM_Field_Multicheckbox' ),
			'hidden'        => array( 'WPUM_Field_Hidden' ),
		);
	}

	/**
	 * @dataProvider types_without_placeholder
	 */
	public function test_placeholder_setting_is_hidden( $class ) {
		$settings = $this->settings_for( new $class() );

		$this->assertArrayNotHasKey( 'placeholder', $settings['general'] );
		$this->assertArrayHasKey( 'field_title', $settings['general'] );
	}

	public function test_text_field_keeps_placeholder_setting() {
		$settings = $this->settings_for( new WPUM_Field_Text() );

		$this->assertArrayHasKey( 'placeholder', $settings['general'] );
	}
}
