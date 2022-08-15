<?php

use voku\helper\HtmlDomParser;

$I = new AcceptanceTester( $scenario );
$I->amOnPage( '/login' );
$I->see( 'Login' );
$I->click( 'Lost your password?' );
$I->see( 'Password Reset' );
$I->fillField( 'username_email', 'wpwp_admin' );
$I->click( 'Reset password' );
$I->waitForEmailWithSubject( "Reset your " . $I->grabOptionFromDatabase( 'blogname' ) . " password" );
$lastMessage = $I->fetchLastMessage();
$dom         = HtmlDomParser::str_get_html( $lastMessage->html_body );
$anchor      = $dom->findOne( 'a' );
$I->amOnUrl( str_replace( '&amp;', '&', $anchor->href ) );
$I->see( 'Enter a new password below' );
