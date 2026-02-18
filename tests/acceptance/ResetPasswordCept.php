<?php

use voku\helper\HtmlDomParser;

$I = new \Step\Acceptance\Administrator( $scenario );
$I->amOnSettingsPage('profiles');
$I->waitForText('Disable Strong Passwords');
$I->checkOption('#disable_strong_passwords');
$I->click('Save Changes');
$I->waitForText( 'Settings successfully saved.' );
$I->logOut();

$I->amOnPage( '/login' );
$I->see( 'Login' );
$I->click( 'Lost your password?' );
$I->see( 'Password Reset' );
$I->fillField( 'username_email', $_ENV['ADMIN_USERNAME'] );
$I->click( 'Reset password' );
$I->see( "We've sent an email to" );
$I->waitForEmailWithSubject( "Reset your " . $I->grabOptionFromDatabase( 'blogname' ) . " password" );
$lastMessage = $I->fetchLastMessage();
$dom         = HtmlDomParser::str_get_html( $lastMessage->html_body );
$anchor      = $dom->findOne( 'a' );
$I->amOnUrl( str_replace( '&amp;', '&', $anchor->href ) );
$I->see( 'Enter a new password below' );
$I->fillField( 'password', $_ENV['ADMIN_PASSWORD'] );
$I->fillField( 'password_2', $_ENV['ADMIN_PASSWORD'] );
$I->click('Reset password');
$I->see('Password successfully reset');
