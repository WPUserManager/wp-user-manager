<?php
/**
 * This class is responsible for loading the profile, groups and data and displaying it.
 *
 * @package     wp-user-manager
 * @copyright   Copyright (c) 2018, Alessandro Tesoro
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fields Query
 */
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
			'number_groups' => 20,
		);

		// Parse incoming $args into an array and merge it with $defaults
		$args = wp_parse_args( $args, $defaults );

		// Retrieve groups.
		$groups = WPUM()->fields_groups->get_groups( array(
			'orderby' => 'group_order',
			'order'   => 'ASC',
			'number'  => $args['number_groups'],
			'fields'  => true,
			'user_id' => $args['user_id'],
		) );

		$this->groups      = $groups;
		$this->group_count = count( $this->groups );
		$this->user_id     = $args['user_id'];

	}

	/**
	 * Whether there are groups available.
	 *
	 * @access public
	 * @return boolean
	 */
	public function has_groups() {
		if ( ! empty( $this->group_count ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get next group within the loop.
	 *
	 * @access public
	 * @return array
	 */
	public function next_group() {

		$this->current_group++;
		$this->group       = $this->groups[ $this->current_group ];
		$this->field_count = 0;
		$this->group       = $this->group;

		if ( ! empty( $this->group->get_fields() ) ) {
			$this->group->fields = apply_filters( 'wpum_group_fields', $this->group->get_fields(), $this->group->get_ID() );
			$this->field_count   = count( $this->group->get_fields() );
		}

		return $this->group;

	}

	/**
	 * Rewind groups.
	 *
	 * @return void
	 */
	public function rewind_groups() {
		$this->current_group = -1;

		if ( $this->group_count > 0 ) {
			$this->group = $this->groups[0];
		}

	}

	/**
	 * Check whether we've reached the end of the loop or keep looping.
	 *
	 * @return bool
	 */
	public function profile_groups() {
		if ( $this->current_group + 1 < $this->group_count ) {
			return true;
		} elseif ( $this->current_group + 1 === $this->group_count ) {
			do_action( 'wpum_field_groups_loop_end' );
			$this->rewind_groups();
		}
		$this->in_the_loop = false;
		return false;
	}

	/**
	 * Setup global variable for current group within the loop.
	 *
	 * @global $wpum_fields_group
	 * @return void
	 */
	public function the_profile_group() {
		global $wpum_fields_group;
		$this->in_the_loop = true;
		$wpum_fields_group = $this->next_group();
		if ( 0 === $this->current_group ) {
			do_action( 'wpum_field_groups_loop_start' );
		}
	}

	/**
	 * Verify whether the current group within the loop has fields.
	 *
	 * @access public
	 *
	 * @param bool $ignore_hidden
	 *
	 * @return bool
	 */
	public function has_fields( $ignore_hidden = false ) {
		$has_data      = false;
		$hidden_fields = 0;

		for ( $i = 0, $count = count( $this->group->fields ); $i < $count; ++$i ) {
			$field = $this->group->fields[ $i ];
			if ( ! empty( $field->get_value() ) || ( '0' === $field->get_value() ) ) {
				$has_data = true;
			}

			if ( 'public' !== $field->get_visibility() ) {
				$hidden_fields++;
			}
		}

		if ( $ignore_hidden && $hidden_fields === $count ) {
			return false;
		}

		return $has_data;
	}


	/**
	 * Proceed to next field within the loop.
	 *
	 * @access public
	 * @return object field details.
	 */
	public function next_field() {
		$this->current_field++;
		$this->field = $this->group->get_fields()[ $this->current_field ];
		return $this->field;
	}

	/**
	 * Cleanup the fields loop once it ends.
	 *
	 * @access public
	 * @return void
	 */
	public function rewind_fields() {
		$this->current_field = -1;
		if ( $this->field_count > 0 ) {
			$fields      = $this->group->get_fields();
			$this->field = $fields[0];
		}
	}

	/**
	 * Start the fields loop.
	 *
	 * @access public
	 * @return mixed
	 */
	public function profile_fields() {
		if ( $this->current_field + 1 < $this->field_count ) {
			return true;
		} elseif ( $this->current_field + 1 === $this->field_count ) {
			$this->rewind_fields();
		}
		return false;
	}

	/**
	 * Setup global variable for field within the loop.
	 *
	 * @access public
	 * @global $wpum_field
	 */
	public function the_profile_field() {
		global $wpum_field;

		$wpum_field = $this->next_field();

		if ( ! empty( $wpum_field->get_value() ) ) {
			$value = maybe_unserialize( $wpum_field->get_value() );
		} else {
			$value = false;
		}

		if ( ! empty( $value ) || ( '0' === $value ) ) {
			$this->field_has_data = true;
			// Now verify if the field is visible or not.
			if ( $wpum_field->get_visibility() !== 'public' ) {
				$this->field_has_data = false;
			}
		} else {
			$this->field_has_data = false;
		}

	}


}
