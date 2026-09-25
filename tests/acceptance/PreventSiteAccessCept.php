<?php

$I = new \Step\Acceptance\Administrator( $scenario );
$I->amOnSettingsPage('general/login');
$I->waitForText('Prevent site access to visitors');
$I->checkOption('#lock_complete_site');
$I->uncheckOption('#lock_wplogin');
$I->uncheckOption('#lock_complete_site_allow_register');
$I->click('Save Changes');
$I->waitForText( 'Settings successfully saved.' );
$I->logOut();


$I->amOnPage( '/' );
$I->seeInCurrentUrl('/login/');
$I->see( 'Login' );

$I->amOnPage( '/register' );
$I->seeInCurrentUrl('/login/');
$I->see( 'Login' );

$I->amOnPage( '/wp/wp-login.php' );
$I->seeInCurrentUrl('/wp/wp-login.php');

$I->amOnPage( '/password-reset/' );
$I->seeInCurrentUrl('/password-reset/');
$I->see( 'Password Reset' );

$I->loginAsAdmin();
$I->amOnSettingsPage('general/login');
$I->waitForText('Allow site registration');
$I->checkOption('#lock_complete_site_allow_register');
$I->click('Save Changes');
$I->waitForText( 'Settings successfully saved.' );
$I->logOut();

$I->amOnPage( '/register/' );
$I->waitForText('Already have an account');
$I->seeInCurrentUrl('/register/');
$I->see( 'Register' );

$I->loginAsAdmin();
$I->amOnSettingsPage('general/login');
$I->waitForText('Lock Access to wp-login.php');
$I->checkOption('#lock_wplogin');
$I->click('Save Changes');
$I->waitForText( 'Settings successfully saved.' );
$I->logOut();


$I->amOnPage( '/wp/wp-login.php' );
$I->seeInCurrentUrl('/login/');

$I->amOnPage( '/wp/wp-login.php?wpum_override=1' );
$I->seeInCurrentUrl('/wp/wp-login.php');








