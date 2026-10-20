<?php

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

/**
 * A fresh install keeps email addresses off public profiles.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/507
 */
class DefaultFieldVisibilityTest extends WPUMTestCase {

	/**
	 * Install the default fields as on a new site.
	 *
	 * @return array Field IDs keyed by field type.
	 */
	private function fresh_install_fields() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->prefix}wpum_fields" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}wpum_fieldmeta" );
		wp_cache_flush();
		delete_option( 'wpum_version_upgraded_from' );

		wpum_install_fields();

		$ids = array();
		foreach ( $wpdb->get_results( "SELECT id, type FROM {$wpdb->prefix}wpum_fields" ) as $row ) {
			$ids[ $row->type ] = (int) $row->id;
		}

		return $ids;
	}

	/**
	 * Read a stored field setting straight from the database.
	 *
	 * @param int    $field_id
	 * @param string $key
	 *
	 * @return string|null
	 */
	private function stored_meta( $field_id, $key ) {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$wpdb->prefix}wpum_fieldmeta WHERE wpum_field_id = %d AND meta_key = %s", $field_id, $key ) );
	}

	public function test_email_field_is_hidden_on_a_fresh_install() {
		$ids = $this->fresh_install_fields();

		$this->assertArrayHasKey( 'user_email', $ids );
		$this->assertSame( 'hidden', $this->stored_meta( $ids['user_email'], 'visibility' ) );
	}

	public function test_email_field_stays_editable_on_the_account_page() {
		$ids = $this->fresh_install_fields();

		$this->assertSame( 'public', $this->stored_meta( $ids['user_email'], 'editing' ) );
	}

	public function test_other_default_fields_stay_public() {
		$ids = $this->fresh_install_fields();

		$this->assertSame( 'public', $this->stored_meta( $ids['username'], 'visibility' ) );
		$this->assertSame( 'public', $this->stored_meta( $ids['user_firstname'], 'visibility' ) );
	}
}
