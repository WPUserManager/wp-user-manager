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
	 * @var int
	 */
	public $current_group = -1;

	/**
	 * The number of groups returned by the query.
	 *
	 * @var int
	 */
	public $group_count;

	/**
	 * List of groups found by the query.
	 *
	 * @var array
	 */
	public $groups;

	/**
	 * The current group object being iterated on.
	 *
	 * @var object
	 */
	public $group;

	/**
	 * The current field.
	 *
	 * @var int
	 */
	public $current_field = -1;

	/**
	 * The field count.
	 *
	 * @var int
	 */
	public $field_count;

	/**
	 * Whether the field has data.
	 *
	 * @var bool
	 */
	public $field_has_data;

	/**
	 * The field.
	 *
	 * @var int
	 */
	public $field;

	/**
	 * Flag to check whether the loop is currently being iterated.
	 *
	 * @var bool
	 */
	public $in_the_loop;

	/**
	 * The user id.
	 *
	 * @var int
	 */
	public $user_id;

	/**
	 * Let's get things going.
	 *
	 * @param array $args arguments.
	 */
	public function __construct( $args = '' ) {

		$defaults = array(
			'user_id'       => wpum_get_queried_user_id(),
			'number_groups' => 20
		);

		// Parse incoming $args into an array and merge it with $defaults
		$args = wp_parse_args( $args, $defaults );

		// Retrieve groups.
		$groups = WPUM()->fields_groups->get_groups( [
			'orderby' => 'group_order',
			'order'   => 'DESC',
			'number'  => $args['number_groups'],
			'fields'  => true
		] );

		$this->groups      = $groups;
		$this->group_count = count( $this->groups );
		$this->user_id     = $args['user_id'];

	}

}
