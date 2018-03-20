<?php
/**
 * Database abstraction layer to work with field groups.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Field_Group {

	/**
	 * Group ID.
	 *
	 * @access protected
	 * @var int
	 */
	protected $id = 0;

	/**
	 * Group order number.
	 *
	 * @access protected
	 * @var int
	 */
	protected $group_order = 0;

	/**
	 * Group Name.
	 *
	 * @access protected
	 * @var string
	 */
	protected $name = null;

	/**
	 * Group Description.
	 *
	 * @access protected
	 * @var string
	 */
	protected $description = null;

	/**
	 * The Database Abstraction
	 */
	protected $db;

}
