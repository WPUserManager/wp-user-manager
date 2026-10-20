<?php
/**
 * Tests for the plain URL and link variants of the login and password recovery email tags.
 *
 * {login_page_url} and {recovery_url} output the URL only, so it can be used in
 * an href. {login_page_link} and {recovery_link} output an anchor in HTML emails.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/309
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailUrlLinkTagsTest extends WPUMTestCase {

	/**
	 * @var WP_User
	 */
	protected $user;

	/**
	 * @var int
	 */
	protected $login_page_id;

	/**
	 * @var int
	 */
	protected $password_page_id;

	/**
	 * Email template the tags should see: 'default' (HTML) or 'none' (plain text).
	 *
	 * @var string
	 */
	protected $template = 'default';

	/**
	 * Captured wp_mail arguments.
	 *
	 * @var array
	 */
	protected $sent_emails = array();

	public function _setUp() {
		parent::_setUp();

		$this->user = self::factory()->user->create_and_get( array(
			'user_login' => 'tag user+' . wp_rand(),
			'user_email' => 'urltags_' . wp_rand() . '@example.com',
		) );

		$this->login_page_id    = self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_title'  => 'Login',
			'post_status' => 'publish',
		) );
		$this->password_page_id = self::factory()->post->create( array(
			'post_type'   => 'page',
			'post_title'  => 'Password Reset',
			'post_status' => 'publish',
		) );

		$this->template    = 'default';
		$this->sent_emails = array();

		add_filter( 'wpum_get_option_login_page', array( $this, 'login_page_option' ) );
		add_filter( 'wpum_get_option_password_recovery_page', array( $this, 'password_page_option' ) );
		add_filter( 'wpum_get_option_email_template', array( $this, 'template_option' ) );
		add_filter( 'wp_mail', array( $this, 'capture_email' ) );
	}

	public function _tearDown() {
		remove_filter( 'wpum_get_option_login_page', array( $this, 'login_page_option' ) );
		remove_filter( 'wpum_get_option_password_recovery_page', array( $this, 'password_page_option' ) );
		remove_filter( 'wpum_get_option_email_template', array( $this, 'template_option' ) );
		remove_filter( 'wp_mail', array( $this, 'capture_email' ) );

		parent::_tearDown();
	}

	public function login_page_option() {
		return array( $this->login_page_id );
	}

	public function password_page_option() {
		return array( $this->password_page_id );
	}

	public function template_option() {
		return $this->template;
	}

	public function capture_email( $args ) {
		$this->sent_emails[] = $args;

		return $args;
	}

	/**
	 * The unescaped reset URL the recovery tags should point to.
	 *
	 * @param string $key Reset key.
	 *
	 * @return string
	 */
	protected function expected_reset_url( $key ) {
		return add_query_arg( array(
			'login'  => rawurlencode( $this->user->user_login ),
			'key'    => $key,
			'action' => 'wpum-reset',
		), get_permalink( $this->password_page_id ) );
	}

	/**
	 * An email object as the tag callbacks receive it.
	 *
	 * @return WPUM_Emails
	 */
	protected function email_object() {
		$emails = new WPUM_Emails();
		$emails->__set( 'user_id', $this->user->ID );
		$emails->__set( 'user_login', $this->user->user_login );

		return $emails;
	}

	/**
	 * Send a message through WPUM_Emails the way the password recovery form does.
	 *
	 * @param string      $message Email content with tags.
	 * @param string|null $key     Reset key, null for emails that don't generate one.
	 *
	 * @return string The sent message body.
	 */
	protected function send( $message, $key = null ) {
		$emails = $this->email_object();

		if ( null !== $key ) {
			$emails->__set( 'password_reset_key', $key );
		}

		$emails->send( $this->user->user_email, 'Subject', $message );

		$this->assertCount( 1, $this->sent_emails, 'Exactly one email should be sent.' );

		return $this->sent_emails[0]['message'];
	}

	public function test_new_tags_are_registered_and_listed() {
		$tags = wp_list_pluck( WPUM()->emails->get_tags(), 'function', 'tag' );

		$this->assertSame( 'wpum_email_tag_login_page_link', $tags['login_page_link'] );
		$this->assertSame( 'wpum_email_tag_password_recovery_link', $tags['recovery_link'] );
		$this->assertSame( 'wpum_email_tag_login_page_url', $tags['login_page_url'] );
		$this->assertSame( 'wpum_email_tag_password_recovery_url', $tags['recovery_url'] );

		$list = wpum_get_emails_tags_list();
		$this->assertStringContainsString( '{login_page_link}', $list );
		$this->assertStringContainsString( '{recovery_link}', $list );
	}

	public function test_login_page_url_is_plain_url_in_html_email() {
		$result = wpum_email_tag_login_page_url( $this->user->ID );

		$this->assertSame( esc_url( get_permalink( $this->login_page_id ) ), $result );
		$this->assertStringNotContainsString( '<a', $result );
	}

	public function test_login_page_url_is_plain_url_in_plain_text_email() {
		$this->template = 'none';

		$this->assertSame( get_permalink( $this->login_page_id ), wpum_email_tag_login_page_url( $this->user->ID ) );
	}

	public function test_login_page_link_is_anchor_in_html_email() {
		$url    = get_permalink( $this->login_page_id );
		$result = wpum_email_tag_login_page_link( $this->user->ID );

		$this->assertSame( '<a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a>', $result );
	}

	public function test_login_page_link_is_plain_url_in_plain_text_email() {
		$this->template = 'none';

		$this->assertSame( get_permalink( $this->login_page_id ), wpum_email_tag_login_page_link( $this->user->ID ) );
	}

	public function test_recovery_url_is_escaped_plain_url_in_html_email() {
		$result = wpum_email_tag_password_recovery_url( $this->user->ID, 'abc123', '', 'recovery_url', $this->email_object() );

		$this->assertSame( esc_url( $this->expected_reset_url( 'abc123' ) ), $result );
		$this->assertStringNotContainsString( '<a', $result );
		$this->assertStringContainsString( '&#038;key=abc123', $result, 'Ampersands should be encoded in HTML emails.' );
	}

	public function test_recovery_url_is_raw_url_in_plain_text_email() {
		$this->template = 'none';

		$result = wpum_email_tag_password_recovery_url( $this->user->ID, 'abc123', '', 'recovery_url', $this->email_object() );

		$this->assertSame( $this->expected_reset_url( 'abc123' ), $result );
		$this->assertStringContainsString( '&key=abc123', $result, 'Plain text emails must not contain HTML entities.' );
	}

	public function test_recovery_link_is_anchor_in_html_email() {
		$url    = $this->expected_reset_url( 'abc123' );
		$result = wpum_email_tag_password_recovery_link( $this->user->ID, 'abc123', '', 'recovery_link', $this->email_object() );

		$this->assertSame( '<a href="' . esc_url( $url ) . '" style="color:#000">' . esc_html( $url ) . '</a>', $result );
	}

	public function test_recovery_link_is_raw_url_in_plain_text_email() {
		$this->template = 'none';

		$result = wpum_email_tag_password_recovery_link( $this->user->ID, 'abc123', '', 'recovery_link', $this->email_object() );

		$this->assertSame( $this->expected_reset_url( 'abc123' ), $result );
	}

	public function test_recovery_link_color_filter_is_escaped() {
		$filter = function () {
			return 'red" onclick="alert(1)';
		};
		add_filter( 'wpum_email_tag_password_recovery_url_color', $filter );

		$result = wpum_email_tag_password_recovery_link( $this->user->ID, 'abc123', '', 'recovery_link', $this->email_object() );

		remove_filter( 'wpum_email_tag_password_recovery_url_color', $filter );

		$this->assertStringContainsString( 'style="color:red&quot; onclick=&quot;alert(1)"', $result );
	}

	public function test_recovery_url_falls_back_to_user_id_for_login() {
		$email = new WPUM_Emails();

		$result = wpum_email_tag_password_recovery_url( $this->user->ID, 'abc123', '', 'recovery_url', $email );

		$this->assertSame( esc_url( $this->expected_reset_url( 'abc123' ) ), $result );
	}

	/**
	 * The documented use case: a button styled by the site owner around {recovery_url}.
	 */
	public function test_password_recovery_email_renders_url_in_href_and_link() {
		$body = $this->send( '<p><a class="button" href="{recovery_url}">Reset</a></p><p>{recovery_link}</p>', 'resetkey1' );

		$url = esc_url( $this->expected_reset_url( 'resetkey1' ) );

		$this->assertStringContainsString( '<a class="button" href="' . $url . '">Reset</a>', $body );
		$this->assertStringContainsString( '<a href="' . $url . '" style="color:#000">', $body );
		$this->assertStringNotContainsString( '{recovery_url}', $body );
		$this->assertStringNotContainsString( '{recovery_link}', $body );
	}

	public function test_plain_text_email_contains_raw_urls_and_no_markup() {
		$this->template = 'none';

		$body = $this->send( '<p>{recovery_link}</p><p>{recovery_url}</p><p>{login_page_link}</p>', 'resetkey2' );

		$this->assertStringContainsString( $this->expected_reset_url( 'resetkey2' ), $body );
		$this->assertStringContainsString( get_permalink( $this->login_page_id ), $body );
		$this->assertStringNotContainsString( '<a', $body );
		$this->assertStringNotContainsString( '&#038;', $body );
	}

	/**
	 * Emails that don't generate a reset key, e.g. the admin notification, must not get one.
	 */
	public function test_recovery_tags_in_email_without_reset_key_have_no_key() {
		$body = $this->send( '<p>{recovery_url}</p><p>{recovery_link}</p>' );

		$this->assertStringNotContainsString( 'key=', $body );
		$this->assertStringContainsString( 'action=wpum-reset', $body );
	}

	public function test_login_page_tags_in_email() {
		$body = $this->send( '<p><a href="{login_page_url}">Log in</a></p><p>{login_page_link}</p>' );

		$url = esc_url( get_permalink( $this->login_page_id ) );

		$this->assertStringContainsString( '<a href="' . $url . '">Log in</a>', $body );
		$this->assertStringContainsString( '<a href="' . $url . '">' . esc_html( get_permalink( $this->login_page_id ) ) . '</a>', $body );
	}

	/**
	 * Default templates must keep sending clickable links now that the _url tags are plain.
	 */
	public function test_default_templates_use_link_tags() {
		$defaults = wpum_get_default_emails();

		$this->assertStringContainsString( '{recovery_link}', $defaults['password_recovery_request']['content'] );
		$this->assertStringNotContainsString( '{recovery_url}', $defaults['password_recovery_request']['content'] );
		$this->assertStringContainsString( '{login_page_link}', $defaults['registration_confirmation']['content'] );
		$this->assertStringNotContainsString( '{login_page_url}', $defaults['registration_confirmation']['content'] );

		$body = $this->send( $defaults['password_recovery_request']['content'], 'resetkey3' );

		$this->assertStringContainsString( '<a href="' . esc_url( $this->expected_reset_url( 'resetkey3' ) ) . '"', $body );
	}
}
