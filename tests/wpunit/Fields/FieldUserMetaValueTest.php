<?php
/**
 * Tests for WPUM_Field::set_user_meta(), the method that loads a field's
 * value for display on user profiles.
 *
 * Regression tests for #396: taxonomy field values not displaying on profiles.
 *
 * The taxonomy field (wpum-custom-fields addon) stores its value as object
 * terms via wp_set_object_terms(), and reads it back for the account form
 * through the `wpum_custom_field_value` filter. The profile only read the
 * Carbon Fields value, which is empty when the Carbon field's options were
 * built before the taxonomy was registered (e.g. a theme registering the
 * taxonomy on `init`), so the field disappeared from the profile.
 */

use WPUM\Carbon_Fields\Container;
use WPUM\Carbon_Fields\Field;

require_once __DIR__ . '/FieldsTestCase.php';

class FieldUserMetaValueTest extends FieldsTestCase {

	const TAXONOMY = 'wpum_test_profession';

	/**
	 * @var int
	 */
	protected $group_id;

	/**
	 * @var int
	 */
	protected $user_id;

	/**
	 * @var callable|null
	 */
	protected $value_callback;

	public function _setUp() {
		parent::_setUp();

		register_taxonomy(
			self::TAXONOMY,
			'user',
			array(
				'public'  => true,
				'rewrite' => false,
				'label'   => 'Profession',
			)
		);

		$this->group_id = $this->groups_db->insert( array( 'name' => 'Taxonomy Test Group' ) );
		$this->user_id  = $this->factory()->user->create( array( 'role' => 'subscriber' ) );

		// The profile formatter for taxonomy fields reads the queried user,
		// which falls back to the current user when no profile is queried.
		wp_set_current_user( $this->user_id );
	}

	public function _tearDown() {
		if ( $this->value_callback ) {
			remove_filter( 'wpum_custom_field_value', $this->value_callback, 10 );
			$this->value_callback = null;
		}

		unregister_taxonomy( self::TAXONOMY );
		set_query_var( 'profile', false );
		wp_set_current_user( 0 );

		parent::_tearDown();
	}

	/**
	 * Create a field with the given type and meta.
	 *
	 * @param string $type Field type.
	 * @param array  $meta Field meta, user_meta_key is set automatically.
	 *
	 * @return \WPUM_Field
	 */
	protected function create_field( $type, $meta = array() ) {
		$field_id = $this->fields_db->insert(
			array(
				'group_id' => $this->group_id,
				'type'     => $type,
				'name'     => 'Test ' . $type,
			)
		);

		$field = new \WPUM_Field( $field_id );
		$field->update_meta( 'user_meta_key', 'wpum_field_' . $field_id );
		$field->update_meta( 'visibility', 'public' );
		$field->update_meta( 'editing', 'public' );

		foreach ( $meta as $key => $value ) {
			$field->update_meta( $key, $value );
		}

		return new \WPUM_Field( $field_id );
	}

	/**
	 * Create a checkbox taxonomy field.
	 *
	 * @return \WPUM_Field
	 */
	protected function create_taxonomy_field() {
		return $this->create_field(
			'taxonomy',
			array(
				'taxonomy'   => self::TAXONOMY,
				'field_type' => 'multicheckbox',
			)
		);
	}

	/**
	 * Hook a callback equivalent to wpum-custom-fields' wpum_taxonomy_field_value(),
	 * which is not loaded in core's test environment.
	 */
	protected function hook_taxonomy_value_callback() {
		$this->value_callback = function ( $value, $field, $user_id ) {
			if ( 'taxonomy' !== $field->get_type() ) {
				return $value;
			}

			$terms = wp_get_object_terms( $user_id, $field->get_meta( 'taxonomy' ) );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				return '';
			}

			return wp_list_pluck( $terms, 'term_id' );
		};

		add_filter( 'wpum_custom_field_value', $this->value_callback, 10, 3 );
	}

	/**
	 * Register the Carbon field the way wpum-custom-fields does, with the
	 * options captured at registration time. An empty options list is what
	 * happens when the taxonomy is registered after Carbon registers fields.
	 *
	 * @param \WPUM_Field $field   The WPUM field.
	 * @param array       $options Carbon field options.
	 */
	protected function register_carbon_set_field( $field, $options ) {
		Container::make( 'user_meta', 'Taxonomy Test ' . $field->get_ID() )
			->set_datastore( new \WPUM_User_Meta_Custom_Datastore() )
			->add_fields(
				array(
					Field::make( 'set', $field->get_meta( 'user_meta_key' ), $field->get_name() )->add_options( $options ),
				)
			);
	}

	/**
	 * Create terms in the test taxonomy.
	 *
	 * @param array $names Term names.
	 *
	 * @return int[] Term IDs.
	 */
	protected function create_terms( $names ) {
		$ids = array();
		foreach ( $names as $name ) {
			$ids[] = self::factory()->term->create(
				array(
					'taxonomy' => self::TAXONOMY,
					'name'     => $name,
				)
			);
		}

		return $ids;
	}

	/**
	 * Terms stored as object terms (the taxonomy field's source of truth)
	 * are what the profile displays, even with no user meta at all.
	 */
	public function test_taxonomy_field_value_is_read_from_object_terms() {
		$field    = $this->create_taxonomy_field();
		$term_ids = $this->create_terms( array( 'Designer', 'Developer' ) );
		$this->hook_taxonomy_value_callback();

		wp_set_object_terms( $this->user_id, $term_ids, self::TAXONOMY );

		$this->assertSame( '', get_user_meta( $this->user_id, $field->get_meta( 'user_meta_key' ), true ), 'Precondition: no user meta is stored for the field.' );

		$field->set_user_meta( $this->user_id );

		$this->assertSame( 'Designer, Developer', $field->get_value() );
	}

	/**
	 * Reproduces the reported bug end to end through Carbon Fields: the
	 * value is saved through the Carbon datastore (as registration does)
	 * alongside the object terms, but the Carbon field's options were built
	 * before the taxonomy existed, so Carbon returns an empty value.
	 */
	public function test_taxonomy_field_displays_when_carbon_options_are_empty() {
		$field    = $this->create_taxonomy_field();
		$term_ids = $this->create_terms( array( 'Developer', 'Tester' ) );
		$this->hook_taxonomy_value_callback();
		$this->register_carbon_set_field( $field, array() );

		// What wpum_taxonomy_field_update() and wpumcf_save_meta_to_user() do on registration.
		wp_set_object_terms( $this->user_id, $term_ids, self::TAXONOMY );
		\WPUM\carbon_set_user_meta( $this->user_id, $field->get_meta( 'user_meta_key' ), array_map( 'strval', $term_ids ) );

		$this->assertEmpty( \WPUM\carbon_get_user_meta( $this->user_id, $field->get_meta( 'user_meta_key' ) ), 'Precondition: Carbon drops values that are not in its (empty) options.' );

		$field->set_user_meta( $this->user_id );

		$this->assertSame( 'Developer, Tester', $field->get_value() );
	}

	/**
	 * A visitor viewing another user's profile sees the profile owner's
	 * terms, not their own, even though Carbon returns an empty value.
	 *
	 * Loads the fields the way the profile "About" tab does
	 * (WPUM_Fields_Query -> get_groups -> get_fields for the queried user),
	 * scoped to this test's group so fields left behind by other tests
	 * don't affect it.
	 */
	public function test_profile_shows_owners_terms_to_another_user() {
		$field    = $this->create_taxonomy_field();
		$owner    = $this->user_id;
		$visitor  = $this->factory()->user->create( array( 'role' => 'subscriber' ) );
		$term_ids = $this->create_terms( array( 'Designer', 'Developer', 'Tester' ) );
		$this->hook_taxonomy_value_callback();
		$this->register_carbon_set_field( $field, array() );

		wp_set_object_terms( $owner, array( $term_ids[0], $term_ids[1] ), self::TAXONOMY );
		\WPUM\carbon_set_user_meta( $owner, $field->get_meta( 'user_meta_key' ), array_map( 'strval', array( $term_ids[0], $term_ids[1] ) ) );
		wp_set_object_terms( $visitor, array( $term_ids[2] ), self::TAXONOMY );

		// The visitor views the owner's profile (default user_id permalink structure).
		update_option( 'wpum_permalink', 'user_id' );
		wp_set_current_user( $visitor );
		set_query_var( 'profile', (string) $owner );

		$this->assertSame( $owner, wpum_get_queried_user_id(), 'Precondition: the owner is the queried profile.' );

		$fields = WPUM()->fields->get_fields(
			array(
				'group_id' => $this->group_id,
				'order'    => 'ASC',
				'orderby'  => 'field_order',
				'user_id'  => wpum_get_queried_user_id(),
			)
		);

		$this->assertCount( 1, $fields );
		$this->assertSame( 'Designer, Developer', $fields[0]->get_value() );
	}

	/**
	 * A taxonomy field with no terms assigned still has no value, so the
	 * profile keeps hiding it.
	 */
	public function test_taxonomy_field_without_terms_has_no_value() {
		$field = $this->create_taxonomy_field();
		$this->create_terms( array( 'Developer' ) );
		$this->hook_taxonomy_value_callback();

		$field->set_user_meta( $this->user_id );

		$this->assertNull( $field->get_value() );
	}

	/**
	 * Regression: a plain custom field (non-wpum_ meta key) still reads its
	 * user meta, and the taxonomy callback leaves it untouched.
	 */
	public function test_non_taxonomy_field_reads_user_meta() {
		$field = $this->create_field( 'text' );
		$field->update_meta( 'user_meta_key', 'my_custom_key' );
		$field = new \WPUM_Field( $field->get_ID() );
		$this->hook_taxonomy_value_callback();

		update_user_meta( $this->user_id, 'my_custom_key', 'Hello world' );

		$field->set_user_meta( $this->user_id );

		$this->assertSame( 'Hello world', $field->get_value() );
	}

	/**
	 * Regression: a Carbon-backed (wpum_ prefixed) field still reads its
	 * Carbon value, and the taxonomy callback leaves it untouched.
	 */
	public function test_non_taxonomy_carbon_field_reads_carbon_value() {
		$field = $this->create_field( 'text' );
		$this->hook_taxonomy_value_callback();

		Container::make( 'user_meta', 'Text Test ' . $field->get_ID() )
			->set_datastore( new \WPUM_User_Meta_Custom_Datastore() )
			->add_fields( array( Field::make( 'text', $field->get_meta( 'user_meta_key' ), $field->get_name() ) ) );

		\WPUM\carbon_set_user_meta( $this->user_id, $field->get_meta( 'user_meta_key' ), 'Carbon value' );

		$field->set_user_meta( $this->user_id );

		$this->assertSame( 'Carbon value', $field->get_value() );
	}

	/**
	 * Regression: primary fields keep their values.
	 */
	public function test_primary_field_reads_user_data() {
		$this->hook_taxonomy_value_callback();
		wp_update_user(
			array(
				'ID'         => $this->user_id,
				'first_name' => 'Ada',
			)
		);

		$first = $this->create_field( 'user_firstname' );

		$this->assertSame( 'user_firstname', $first->get_primary_id(), 'Precondition: the field is a primary field.' );

		$first->set_user_meta( $this->user_id );

		$this->assertSame( 'Ada', $first->get_value() );
	}

	/**
	 * The filter receives the field and the user being displayed.
	 */
	public function test_set_user_meta_applies_custom_field_value_filter() {
		$field = $this->create_field( 'text' );
		$field->update_meta( 'user_meta_key', 'another_key' );
		$field = new \WPUM_Field( $field->get_ID() );

		$seen = array();

		$this->value_callback = function ( $value, $filtered_field, $user_id ) use ( &$seen ) {
			$seen = array( $value, $filtered_field->get_ID(), $user_id );

			return 'Filtered';
		};
		add_filter( 'wpum_custom_field_value', $this->value_callback, 10, 3 );

		update_user_meta( $this->user_id, 'another_key', 'Stored' );

		$field->set_user_meta( $this->user_id );

		$this->assertSame( array( 'Stored', $field->get_ID(), $this->user_id ), $seen );
		$this->assertSame( 'Filtered', $field->get_value() );
	}
}
