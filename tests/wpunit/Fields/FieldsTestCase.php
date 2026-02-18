<?php
/**
 * Base test case for WPUM fields system tests.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

abstract class FieldsTestCase extends WPUMTestCase {

	/**
	 * @var \WPUM_DB_Fields
	 */
	protected $fields_db;

	/**
	 * @var \WPUM_DB_Fields_Groups
	 */
	protected $groups_db;

	/**
	 * @var \WPUM_DB_Field_Meta
	 */
	protected $field_meta_db;

	public function _setUp() {
		parent::_setUp();

		$this->fields_db     = new \WPUM_DB_Fields();
		$this->groups_db     = new \WPUM_DB_Fields_Groups();
		$this->field_meta_db = new \WPUM_DB_Field_Meta();
	}
}
