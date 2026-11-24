<?php
/**
 * Block output and block restrictions from the wpum-blocks package.
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class BlocksTest extends WPUMTestCase {

	/**
	 * @var string Output the block callback returns in these tests.
	 */
	protected $block_output = '';

	/**
	 * @var bool Whether the test shortcode ran.
	 */
	protected $shortcode_ran = false;

	public function setUp(): void {
		parent::setUp();

		add_shortcode( 'wpum_test_marker', array( $this, 'test_shortcode' ) );
		add_filter( 'wpum_blocks_block_callback', array( $this, 'block_callback' ) );

		$this->shortcode_ran = false;
		wp_set_current_user( 0 );
	}

	public function tearDown(): void {
		remove_shortcode( 'wpum_test_marker' );
		remove_filter( 'wpum_blocks_block_callback', array( $this, 'block_callback' ) );
		$_GET = array();
		unset( $GLOBALS['current_screen'], $GLOBALS['post'] );
		parent::tearDown();
	}

	public function test_shortcode() {
		$this->shortcode_ran = true;

		return 'SHORTCODE-RAN';
	}

	public function block_callback() {
		return function () {
			return $this->block_output;
		};
	}

	protected function render_login_block() {
		$this->assertTrue( WP_Block_Type_Registry::get_instance()->is_registered( 'wpum/login-form' ) );

		return apply_filters( 'the_content', '<!-- wp:wpum/login-form /-->' );
	}

	public function test_shortcodes_in_block_output_are_not_run() {
		$this->block_output = '<p>[wpum_test_marker]</p>';

		$html = $this->render_login_block();

		$this->assertFalse( $this->shortcode_ran );
		$this->assertStringNotContainsString( 'SHORTCODE-RAN', $html );
		$this->assertStringContainsString( '&#91;wpum_test_marker&#93;', $html );
	}

	public function test_brackets_in_attributes_are_encoded() {
		$this->block_output = '<input type="text" name="field[]" value="[wpum_test_marker]">';

		$html = $this->render_login_block();

		$this->assertFalse( $this->shortcode_ran );
		$this->assertStringContainsString( 'name="field&#91;&#93;"', $html );
	}

	public function test_script_and_style_contents_are_unchanged() {
		$this->block_output = '<script>var list = [1, 2];</script><style>a[href] { color: red; }</style>';

		$html = $this->render_login_block();

		$this->assertStringContainsString( 'var list = [1, 2];', $html );
		$this->assertStringContainsString( 'a[href] { color: red; }', $html );
	}

	protected function logged_in_block() {
		return array(
			'blockName' => 'core/paragraph',
			'attrs'     => array(
				'wpum_restrict_type'         => 'wpum_restrict_type_state',
				'wpum_restrict_state'        => 'in',
				'wpum_restrict_show_message' => false,
			),
		);
	}

	public function test_editor_request_does_not_show_restricted_block_to_visitors() {
		$GLOBALS['post'] = get_post( self::factory()->post->create() );
		set_current_screen( 'post' );
		$_GET['action'] = 'edit';

		$blocks = \WPUM\WPUM_Blocks::get_instance();

		$this->assertSame( '', $blocks->maybe_restrict_content( 'Members only', $this->logged_in_block() ) );
	}

	public function test_editor_request_shows_restricted_block_to_users_who_can_edit() {
		$GLOBALS['post'] = get_post( self::factory()->post->create() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );
		set_current_screen( 'post' );
		$_GET['action'] = 'edit';

		$blocks = \WPUM\WPUM_Blocks::get_instance();

		$this->assertSame( 'Members only', $blocks->maybe_restrict_content( 'Members only', $this->logged_in_block() ) );
	}

	public function test_editor_request_does_not_show_role_restricted_block_to_subscribers() {
		$GLOBALS['post'] = get_post( self::factory()->post->create() );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		set_current_screen( 'post' );
		$_GET['action'] = 'edit';

		$block = array(
			'blockName' => 'core/paragraph',
			'attrs'     => array(
				'wpum_restrict_type'         => 'wpum_restrict_type_role',
				'wpum_restrict_roles'        => array( 'editor' ),
				'wpum_restrict_show_message' => false,
			),
		);

		$blocks = \WPUM\WPUM_Blocks::get_instance();

		$this->assertSame( '', $blocks->maybe_restrict_content( 'Editors only', $block ) );
	}
}
