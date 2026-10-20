<?php
/**
 * The 2.9.27 upgrade switches stored emails from {recovery_url} and
 * {login_page_url}, which used to output a link and now output the plain URL,
 * to {recovery_link} and {login_page_link}, so they keep sending a link.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/309
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailUrlTagsUpgradeTest extends WPUMTestCase {

	public function _tearDown() {
		delete_option( 'wpum_email' );

		parent::_tearDown();
	}

	protected function run_upgrade() {
		$upgrade = new \ReflectionMethod( 'WPUM_Plugin_Updates', 'upgrade_v2_9_27' );
		$upgrade->setAccessible( true );
		$upgrade->invoke( ( new \ReflectionClass( 'WPUM_Plugin_Updates' ) )->newInstanceWithoutConstructor() );
	}

	/**
	 * The emails every site was installed with before 2.9.27.
	 *
	 * @return array
	 */
	protected function pre_2_9_27_emails() {
		return array(
			'registration_confirmation'       => array(
				'subject' => 'Welcome to {sitename}',
				'title'   => 'Welcome to {sitename}',
				'content' => '<p>Username: {username}<br />Login page: {login_page_url}<br />Password: {password}</p>',
				'footer'  => '<a href="{siteurl}">{sitename}</a>',
			),
			'registration_admin_notification' => array(
				'subject' => '[{sitename}] New User Registration',
				'title'   => 'New User Registration',
				'content' => '<p>Username: {username}</p>',
			),
			'password_recovery_request'       => array(
				'subject' => 'Reset your {sitename} password',
				'title'   => 'Reset your {sitename} password',
				'content' => '<p>To reset your password, visit the following address:</p><p>{recovery_url}</p>',
				'footer'  => '<a href="{siteurl}">{sitename}</a>',
			),
		);
	}

	public function test_converts_text_tags_to_link_tags() {
		$this->assertSame(
			'<p>{recovery_link}</p> Login page: {login_page_link}',
			wpum_email_url_tags_to_link_tags( '<p>{recovery_url}</p> Login page: {login_page_url}' )
		);
	}

	public function test_keeps_tags_used_as_attribute_values() {
		$content = '<a class="button" href="{recovery_url}">Reset</a> <a href=\'{login_page_url}\'>Log in</a> <a href={recovery_url}>x</a>';

		$this->assertSame( $content, wpum_email_url_tags_to_link_tags( $content ) );
	}

	public function test_converts_text_tag_next_to_an_attribute_use() {
		$this->assertSame(
			'<a href="{recovery_url}">Reset</a> or copy {recovery_link}',
			wpum_email_url_tags_to_link_tags( '<a href="{recovery_url}">Reset</a> or copy {recovery_url}' )
		);
	}

	public function test_leaves_other_content_alone() {
		$content = '<p>Hello {username}, {recovery_link} {login_page_link} {siteurl}</p>';

		$this->assertSame( $content, wpum_email_url_tags_to_link_tags( $content ) );
		$this->assertSame( '', wpum_email_url_tags_to_link_tags( '' ) );
	}

	public function test_upgrade_converts_stored_default_emails() {
		update_option( 'wpum_email', $this->pre_2_9_27_emails() );

		$this->run_upgrade();

		$emails = get_option( 'wpum_email' );

		$this->assertStringContainsString( '{recovery_link}', $emails['password_recovery_request']['content'] );
		$this->assertStringNotContainsString( '{recovery_url}', $emails['password_recovery_request']['content'] );
		$this->assertStringContainsString( 'Login page: {login_page_link}', $emails['registration_confirmation']['content'] );
	}

	public function test_upgrade_keeps_everything_else() {
		$before = $this->pre_2_9_27_emails();
		update_option( 'wpum_email', $before );

		$this->run_upgrade();

		$after = get_option( 'wpum_email' );

		$this->assertSame( array_keys( $before ), array_keys( $after ) );
		$this->assertSame( $before['registration_admin_notification'], $after['registration_admin_notification'] );
		$this->assertSame( $before['password_recovery_request']['subject'], $after['password_recovery_request']['subject'] );
		$this->assertSame( $before['password_recovery_request']['footer'], $after['password_recovery_request']['footer'] );
	}

	public function test_upgrade_leaves_the_subject_as_plain_url() {
		update_option( 'wpum_email', array(
			'password_recovery_request' => array(
				'subject' => 'Reset at {recovery_url}',
				'content' => '<p>{recovery_url}</p>',
			),
		) );

		$this->run_upgrade();

		$emails = get_option( 'wpum_email' );

		$this->assertSame( 'Reset at {recovery_url}', $emails['password_recovery_request']['subject'] );
		$this->assertSame( '<p>{recovery_link}</p>', $emails['password_recovery_request']['content'] );
	}

	public function test_upgrade_converts_addon_emails_too() {
		update_option( 'wpum_email', array(
			'some_addon_email' => array( 'content' => 'Log in at {login_page_url}' ),
		) );

		$this->run_upgrade();

		$emails = get_option( 'wpum_email' );

		$this->assertSame( 'Log in at {login_page_link}', $emails['some_addon_email']['content'] );
	}

	public function test_upgrade_is_idempotent() {
		update_option( 'wpum_email', $this->pre_2_9_27_emails() );

		$this->run_upgrade();
		$once = get_option( 'wpum_email' );

		$this->run_upgrade();

		$this->assertSame( $once, get_option( 'wpum_email' ) );
	}

	public function non_array_values() {
		return array(
			'missing' => array( false ),
			'string'  => array( 'not an array' ),
			'integer' => array( 1 ),
		);
	}

	/**
	 * @dataProvider non_array_values
	 */
	public function test_upgrade_ignores_a_non_array_option( $value ) {
		if ( false === $value ) {
			delete_option( 'wpum_email' );
		} else {
			update_option( 'wpum_email', $value );
		}

		$this->run_upgrade();

		$this->assertSame( $value, get_option( 'wpum_email' ) );
	}

	public function test_upgrade_skips_malformed_email_entries() {
		update_option( 'wpum_email', array(
			'broken'                    => 'not an array',
			'password_recovery_request' => array(
				'content' => array( 'not a string' ),
				'footer'  => '{recovery_url}',
			),
		) );

		$this->run_upgrade();

		$emails = get_option( 'wpum_email' );

		$this->assertSame( 'not an array', $emails['broken'] );
		$this->assertSame( array( 'not a string' ), $emails['password_recovery_request']['content'] );
		$this->assertSame( '{recovery_link}', $emails['password_recovery_request']['footer'] );
	}

	public function test_upgraded_defaults_match_what_new_installs_get() {
		$defaults = wpum_get_default_emails();
		$old      = $defaults;

		// Pre-2.9.27 installs stored the same defaults with the URL tags.
		foreach ( $old as $email_id => $email ) {
			$old[ $email_id ]['content'] = str_replace( array( '{recovery_link}', '{login_page_link}' ), array( '{recovery_url}', '{login_page_url}' ), $email['content'] );
		}
		$this->assertNotSame( $defaults, $old, 'The defaults should use the link tags.' );

		update_option( 'wpum_email', $old );
		$this->run_upgrade();

		$this->assertSame( $defaults, get_option( 'wpum_email' ) );
	}
}
