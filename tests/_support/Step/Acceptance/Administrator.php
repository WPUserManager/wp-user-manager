<?php

namespace Step\Acceptance;

use Codeception\Scenario;

class Administrator extends \AcceptanceTester {

	/**
	 * Administrator constructor.
	 *
	 * @param Scenario $scenario
	 */
	public function __construct( Scenario $scenario ) {
		parent::__construct( $scenario );
		$this->loginAsAdmin();
	}

	public function amOnSettingsPage( $path = 'general' ) {
		$this->amOnAdminPage( 'users.php?page=wpum-settings#/' . $path );
	}

	public function loginAsAdmin( $timeout = 10, $maxAttempts = 5 ) {
		$this->amOnPage('/login/');
		$this->waitForElement('#username', $timeout);
		$this->waitForElement('#password', $timeout);
		$this->fillField('username', $_ENV['ADMIN_USERNAME'] );
		$this->fillField('password', $_ENV['ADMIN_PASSWORD']);
		$this->click(['name' => 'submit_login']);
		$this->waitForText('You are currently logged in');
	}

	public function logOut( $redirectTo = false ) {
		$this->amOnPage( 'wp/wp-login.php?action=logout' );
		// Use XPath to have a better performance and find the link in any language.
		$this->click( "//a[contains(@href,'action=logout')]" );
		$this->seeInCurrentUrl( '/login/' );
	}

}
