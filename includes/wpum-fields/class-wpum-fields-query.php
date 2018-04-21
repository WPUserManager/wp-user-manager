<?php
/**
 * This class is responsible for loading the profile, groups and data and displaying it.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPUM_Fields_Query {

	/**
	 * The loop iterator.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $current_group = -1;

	/**
	 * The number of groups returned by the query.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $group_count;

	/**
	 * List of groups found by the query.
	 *
	 * @since 1.2.0
	 * @var array
	 */
	public $groups;

	/**
	 * The current group object being iterated on.
	 *
	 * @since 1.2.0
	 * @var object
	 */
	public $group;

	/**
	 * The current field.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $current_field = -1;

	/**
	 * The field count.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $field_count;

	/**
	 * Whether the field has data.
	 *
	 * @since 1.2.0
	 * @var bool
	 */
	public $field_has_data;

	/**
	 * The field.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $field;

	/**
	 * Flag to check whether the loop is currently being iterated.
	 *
	 * @since 1.2.0
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The user id.
	 *
	 * @since 1.2.0
	 * @var int
	 */
	public $user_id;

	/**
	 * Let's get things going.
	 *
	 * @since 1.2.0
	 * @param array $args arguments.
	 */
	public function __construct( $args = '' ) {

		$defaults = array(
			'user_id'           => false,
			'field_group_id'    => false,
		);

		// Parse incoming $args into an array and merge it with $defaults
		$args = wp_parse_args( $args, $defaults );

		$this->groups      = wpum_get_fields_groups( $args );
		$this->group_count = count( $this->groups );
		$this->user_id     = $args['user_id'];

	}

}
