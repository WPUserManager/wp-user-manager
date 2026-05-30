<?php
/**
 * Tests that WPUM_Field::setup_field() ignores legacy/orphaned columns from
 * old wpum_fields table schemas, preventing PHP 8.2+ dynamic property deprecations.
 */

require_once __DIR__ . '/FieldsTestCase.php';

class FieldLegacyColumnsTest extends FieldsTestCase {

	/**
	 * @var int
	 */
	private $test_group_id;

	/**
	 * Legacy columns that existed in old wpum_fields schemas before fieldmeta migration.
	 *
	 * @var array
	 */
	private $legacy_columns = array(
		'is_required',
		'show_on_registration',
		'can_delete',
		'default_visibility',
		'allow_custom_visibility',
		'options',
		'meta',
	);

	public function _setUp() {
		parent::_setUp();

		$groups = $this->groups_db->get_groups( array( 'primary' => true ) );
		if ( ! empty( $groups ) ) {
			$this->test_group_id = $groups[0]->get_ID();
		} else {
			$this->test_group_id = $this->groups_db->insert( array(
				'name'       => 'Test Group',
				'is_primary' => 1,
			) );
		}

		$this->add_legacy_columns();
	}

	public function _tearDown() {
		$this->drop_legacy_columns();
		parent::_tearDown();
	}

	/**
	 * Add legacy columns to wpum_fields to simulate a pre-migration database.
	 */
	private function add_legacy_columns() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpum_fields';

		foreach ( $this->legacy_columns as $column ) {
			// Only add if not already present (idempotent).
			$exists = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'" ); // phpcs:ignore
			if ( empty( $exists ) ) {
				$wpdb->query( "ALTER TABLE `{$table}` ADD COLUMN `{$column}` VARCHAR(255) DEFAULT NULL" ); // phpcs:ignore
			}
		}
	}

	/**
	 * Remove the legacy columns after the test.
	 */
	private function drop_legacy_columns() {
		global $wpdb;
		$table = $wpdb->prefix . 'wpum_fields';

		foreach ( $this->legacy_columns as $column ) {
			$exists = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'" ); // phpcs:ignore
			if ( ! empty( $exists ) ) {
				$wpdb->query( "ALTER TABLE `{$table}` DROP COLUMN `{$column}`" ); // phpcs:ignore
			}
		}
	}

	/**
	 * Loading a WPUM_Field when the database table has legacy orphaned columns
	 * must not trigger E_DEPRECATED dynamic property notices.
	 */
	public function test_no_dynamic_property_deprecations_with_legacy_columns() {
		$field_id = $this->fields_db->insert( array(
			'group_id'    => $this->test_group_id,
			'type'        => 'text',
			'name'        => 'Legacy Column Test Field',
			'field_order' => 99,
		) );

		$this->assertGreaterThan( 0, $field_id );

		$deprecations = array();
		set_error_handler( function( $errno, $errstr ) use ( &$deprecations ) {
			if ( E_DEPRECATED === $errno && false !== strpos( $errstr, 'dynamic property' ) ) {
				$deprecations[] = $errstr;
			}
			return false; // Let the default handler run too.
		} );

		new WPUM_Field( $field_id );

		restore_error_handler();

		$this->assertEmpty(
			$deprecations,
			'WPUM_Field should not create dynamic properties when legacy columns are present. Got: ' . implode( ', ', $deprecations )
		);
	}

	/**
	 * Legacy column values should not be set on the WPUM_Field object.
	 */
	public function test_legacy_columns_are_not_set_on_field_object() {
		$field_id = $this->fields_db->insert( array(
			'group_id'    => $this->test_group_id,
			'type'        => 'text',
			'name'        => 'Legacy Property Test Field',
			'field_order' => 99,
		) );

		$field = new WPUM_Field( $field_id );

		foreach ( $this->legacy_columns as $column ) {
			$this->assertFalse(
				isset( $field->$column ),
				"Legacy column '{$column}' should not be set as a property on WPUM_Field."
			);
		}
	}
}
