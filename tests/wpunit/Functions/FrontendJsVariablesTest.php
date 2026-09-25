<?php
/**
 * Tests for the variables handed to the frontend script as `wpumFrontend`.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/202
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class FrontendJsVariablesTest extends WPUMTestCase {

	public function _tearDown() {
		remove_all_filters( 'wpum_field_datepicker_disable_mobile' );
		remove_all_filters( 'wpum_field_datepicker_date_format' );
		remove_all_filters( 'pre_option_date_format' );

		parent::_tearDown();
	}

	/**
	 * Issue #202: the datepicker must use the flatpickr calendar on mobile
	 * devices rather than flatpickr's native input fallback, so the frontend
	 * script is told to disable that fallback by default.
	 */
	public function test_issue_202_disable_mobile_is_enabled_by_default() {
		$variables = wpum_get_frontend_js_variables();

		$this->assertArrayHasKey( 'disableMobile', $variables );
		$this->assertEquals( '1', $variables['disableMobile'] );
	}

	/**
	 * Issue #202: sites that prefer the native mobile date input can opt back
	 * into it with the filter.
	 */
	public function test_issue_202_disable_mobile_can_be_filtered_off() {
		add_filter( 'wpum_field_datepicker_disable_mobile', '__return_false' );

		$variables = wpum_get_frontend_js_variables();

		$this->assertEquals( '', $variables['disableMobile'] );
	}

	/**
	 * The value survives wp_localize_script's string casting as a JS truthy or
	 * falsy value, so the script can read it with a boolean coercion.
	 */
	public function test_issue_202_disable_mobile_is_a_scalar_string() {
		$variables = wpum_get_frontend_js_variables();

		$this->assertIsString( $variables['disableMobile'] );
	}

	/**
	 * The existing date format variable is unchanged.
	 */
	public function test_date_format_uses_the_site_date_format() {
		// A filter rather than update_option(), so the site setting can't leak
		// into later tests even if the database isn't rolled back.
		add_filter(
			'pre_option_date_format',
			function () {
				return 'd/m/Y';
			}
		);

		$variables = wpum_get_frontend_js_variables();

		$this->assertEquals( 'd/m/Y', $variables['dateFormat'] );
	}

	/**
	 * The date format remains filterable.
	 */
	public function test_date_format_can_be_filtered() {
		add_filter(
			'wpum_field_datepicker_date_format',
			function () {
				return 'Y-m-d';
			}
		);

		$variables = wpum_get_frontend_js_variables();

		$this->assertEquals( 'Y-m-d', $variables['dateFormat'] );
	}
}
