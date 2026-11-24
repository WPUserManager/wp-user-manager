<?php
/**
 * Restricted Elementor widgets: rendering, the plain text copy Elementor saves
 * to post_content, and the clean-up of copies saved before.
 *
 * Elementor isn't installed in the test environment, so widgets are stand-ins
 * with the one method the restriction filter uses.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class ElementorRestrictedWidgetTest extends WPUMTestCase {

	/**
	 * @var WPUM_Elementor_Loader
	 */
	protected $loader;

	public function _setUp() {
		parent::_setUp();

		$this->loader = WPUM_Elementor_Loader::get_instance();
		$this->loader->end_document_save();
		wp_set_current_user( 0 );
	}

	public function _tearDown() {
		$this->loader->end_document_save();
		remove_filter( 'wp_doing_ajax', '__return_true' );
		unset( $GLOBALS['current_screen'] );
		$_GET = array();
		parent::_tearDown();
	}

	protected function widget( array $settings ) {
		return new class( $settings ) {
			private $settings;

			public function __construct( $settings ) {
				$this->settings = array_merge(
					array(
						'wpum_restrict_type'  => 'wpum_restrict_type_state',
						'wpum_restrict_state' => '',
					),
					$settings
				);
			}

			public function get_settings() {
				return $this->settings;
			}
		};
	}

	protected function render( array $settings ) {
		return $this->loader->wpum_restrict_widget_content( 'Widget content', $this->widget( $settings ) );
	}

	public function test_saved_plain_text_leaves_out_restricted_widgets() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'wp_doing_ajax', '__return_true' );
		set_current_screen( 'dashboard' );

		do_action( 'elementor/document/before_save', null, array() );

		$this->assertSame( '', $this->render( array( 'wpum_restrict_state' => 'in' ) ) );
		$this->assertSame( '', $this->render( array( 'wpum_restrict_type' => 'wpum_restrict_type_role', 'wpum_restrict_roles' => array( 'administrator' ) ) ) );
		$this->assertSame( 'Widget content', $this->render( array() ) );

		do_action( 'elementor/document/after_save', null, array() );

		$this->assertSame( 'Widget content', $this->render( array( 'wpum_restrict_state' => 'in' ) ) );
	}

	public function test_ajax_request_does_not_show_restricted_widgets_to_visitors() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		set_current_screen( 'dashboard' );

		$this->assertSame( '', $this->render( array( 'wpum_restrict_state' => 'in' ) ) );
	}

	public function test_ajax_request_does_not_show_role_restricted_widgets_to_subscribers() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		add_filter( 'wp_doing_ajax', '__return_true' );
		set_current_screen( 'dashboard' );

		$this->assertSame( '', $this->render( array( 'wpum_restrict_type' => 'wpum_restrict_type_role', 'wpum_restrict_roles' => array( 'editor' ) ) ) );
	}

	public function test_editor_requests_show_restricted_widgets_to_users_who_can_edit() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		set_current_screen( 'post' );
		$_GET['action'] = 'elementor';

		$this->assertSame( 'Widget content', $this->render( array( 'wpum_restrict_type' => 'wpum_restrict_type_role', 'wpum_restrict_roles' => array( 'administrator' ) ) ) );
	}

	public function test_editor_requests_do_not_show_restricted_widgets_to_visitors() {
		set_current_screen( 'post' );
		$_GET['action'] = 'elementor';

		$this->assertSame( '', $this->render( array( 'wpum_restrict_state' => 'in' ) ) );
	}

	/**
	 * A loader whose plain text is rendered from one restricted and one open
	 * widget, through the real restriction filter.
	 */
	protected function cleanup_loader() {
		$test = $this;

		return new class( $test ) extends WPUM_Elementor_Loader {
			private $test;

			public function __construct( $test ) {
				$this->test = $test;
			}

			protected function get_plain_text( $post_id ) {
				$restricted = $this->test->make_widget( array( 'wpum_restrict_state' => 'in' ) );
				$open       = $this->test->make_widget( array() );

				return trim( $this->wpum_restrict_widget_content( 'Members text', $restricted ) . ' ' . $this->wpum_restrict_widget_content( 'Public text', $open ) );
			}
		};
	}

	public function make_widget( array $settings ) {
		return $this->widget( $settings );
	}

	protected function elementor_post( $content, $data, $mode = 'builder' ) {
		$post_id = self::factory()->post->create( array(
			'post_content'  => $content,
			'post_modified' => '2026-01-01 00:00:00',
		) );
		update_post_meta( $post_id, '_elementor_data', $data );
		update_post_meta( $post_id, '_elementor_edit_mode', $mode );

		return $post_id;
	}

	public function test_clean_up_rewrites_plain_text_of_posts_with_restricted_widgets() {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$restricted = $this->elementor_post( 'Members text Public text', '[{"settings":{"wpum_restrict_state":"in"}}]' );
		$open       = $this->elementor_post( 'Unchanged', '[{"settings":{"title":"Hello"}}]' );
		$classic    = $this->elementor_post( 'Classic content', '[{"settings":{"wpum_restrict_state":"in"}}]', '' );
		$modified   = get_post_field( 'post_modified', $restricted );

		$this->assertTrue( $this->cleanup_loader()->clean_up_plain_text() );

		clean_post_cache( $restricted );
		$this->assertSame( 'Public text', get_post_field( 'post_content', $restricted ) );
		$this->assertSame( $modified, get_post_field( 'post_modified', $restricted ) );
		$this->assertSame( 'Unchanged', get_post_field( 'post_content', $open ) );
		$this->assertSame( 'Classic content', get_post_field( 'post_content', $classic ) );
	}

	public function test_clean_up_runs_in_batches() {
		$first  = $this->elementor_post( 'Members text Public text', '[{"settings":{"wpum_restrict_type":"wpum_restrict_type_role"}}]' );
		$second = $this->elementor_post( 'Members text Public text', '[{"settings":{"wpum_restrict_users":["1"]}}]' );
		$loader = $this->cleanup_loader();

		$this->assertSame( $first, $loader->clean_up_plain_text( $first - 1, 1 ) );
		$this->assertSame( $second, $loader->clean_up_plain_text( $first, 1 ) );
		$this->assertTrue( $loader->clean_up_plain_text( $second, 1 ) );

		clean_post_cache( $second );
		$this->assertSame( 'Public text', get_post_field( 'post_content', $second ) );
	}
}
