<?php
/**
 * A one-off upgrade switches stored emails from {recovery_url} and
 * {login_page_url}, which used to output a link and now output the plain URL,
 * to {recovery_link} and {login_page_link}, so they keep sending a link.
 *
 * @see https://github.com/WPUserManager/wp-user-manager/issues/309
 */

require_once dirname( __DIR__ ) . '/WPUMTestCase.php';

class EmailUrlTagsUpgradeTest extends WPUMTestCase {

	public function _tearDown() {
		delete_option( 'wpum_email' );
		delete_option( 'wpum_email_link_tags_upgraded' );

		parent::_tearDown();
	}

	protected function run_upgrade() {
		$upgrade = new \ReflectionMethod( 'WPUM_Plugin_Updates', 'upgrade_email_link_tags' );
		$upgrade->setAccessible( true );
		$upgrade->invoke( new WPUM_Plugin_Updates() );
	}

	/**
	 * The emails every site was installed with before the link tags existed.
	 *
	 * @return array
	 */
	protected function legacy_emails() {
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

	public function test_converts_quoted_text() {
		$this->assertSame(
			'<p>Reset: "{recovery_link}"</p><p>\'{login_page_link}\'</p>',
			wpum_email_url_tags_to_link_tags( '<p>Reset: "{recovery_url}"</p><p>\'{login_page_url}\'</p>' )
		);
	}

	public function test_keeps_tags_in_attributes_with_whitespace() {
		$content = '<a href=" {recovery_url}">Reset</a> <a href= {login_page_url}>Log in</a> <a title="x" href = "{recovery_url}" class="b">y</a>';

		$this->assertSame( $content, wpum_email_url_tags_to_link_tags( $content ) );
	}

	public function test_converts_tags_outside_html() {
		$this->assertSame( 'Reset: {recovery_link}', wpum_email_url_tags_to_link_tags( 'Reset: {recovery_url}' ) );
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
		update_option( 'wpum_email', $this->legacy_emails() );

		$this->run_upgrade();

		$emails = get_option( 'wpum_email' );

		$this->assertStringContainsString( '{recovery_link}', $emails['password_recovery_request']['content'] );
		$this->assertStringNotContainsString( '{recovery_url}', $emails['password_recovery_request']['content'] );
		$this->assertStringContainsString( 'Login page: {login_page_link}', $emails['registration_confirmation']['content'] );
	}

	public function test_upgrade_keeps_everything_else() {
		$before = $this->legacy_emails();
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
		update_option( 'wpum_email', $this->legacy_emails() );

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

		// Options come back from the database as strings, so compare with what was stored.
		$stored = get_option( 'wpum_email' );

		$this->run_upgrade();

		$this->assertSame( $stored, get_option( 'wpum_email' ) );
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

	public function test_runs_on_init() {
		global $wp_filter;

		$hooked = false;

		foreach ( $wp_filter['init']->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( is_array( $callback['function'] ) && $callback['function'][0] instanceof WPUM_Plugin_Updates && 'maybe_upgrade_email_link_tags' === $callback['function'][1] ) {
					$hooked = true;
				}
			}
		}

		$this->assertTrue( $hooked );
	}

	public function test_runs_once_on_a_site_with_old_emails() {
		delete_option( 'wpum_email_link_tags_upgraded' );
		update_option( 'wpum_email', $this->legacy_emails() );

		( new WPUM_Plugin_Updates() )->maybe_upgrade_email_link_tags();

		$this->assertStringContainsString( '{recovery_link}', get_option( 'wpum_email' )['password_recovery_request']['content'] );
		$this->assertTrue( (bool) get_option( 'wpum_email_link_tags_upgraded' ) );
	}

	public function test_does_not_run_again_after_the_flag_is_set() {
		delete_option( 'wpum_email_link_tags_upgraded' );
		update_option( 'wpum_email', array() );
		( new WPUM_Plugin_Updates() )->maybe_upgrade_email_link_tags();

		// An admin who later chooses {recovery_url} as text keeps it.
		$emails = array( 'password_recovery_request' => array( 'content' => '<p>{recovery_url}</p>' ) );
		update_option( 'wpum_email', $emails );

		( new WPUM_Plugin_Updates() )->maybe_upgrade_email_link_tags();

		$this->assertSame( $emails, get_option( 'wpum_email' ) );
	}

	public function test_sets_the_flag_when_there_are_no_stored_emails() {
		delete_option( 'wpum_email_link_tags_upgraded' );
		delete_option( 'wpum_email' );

		( new WPUM_Plugin_Updates() )->maybe_upgrade_email_link_tags();

		$this->assertFalse( get_option( 'wpum_email' ) );
		$this->assertTrue( (bool) get_option( 'wpum_email_link_tags_upgraded' ) );
	}

	public function test_upgraded_defaults_match_what_new_installs_get() {
		$defaults = wpum_get_default_emails();
		$old      = $defaults;

		// Older installs stored the same defaults with the URL tags.
		foreach ( $old as $email_id => $email ) {
			$old[ $email_id ]['content'] = str_replace( array( '{recovery_link}', '{login_page_link}' ), array( '{recovery_url}', '{login_page_url}' ), $email['content'] );
		}
		$this->assertNotSame( $defaults, $old, 'The defaults should use the link tags.' );

		update_option( 'wpum_email', $old );
		$this->run_upgrade();

		$this->assertSame( $defaults, get_option( 'wpum_email' ) );
	}
}
