<?php

use voku\helper\HtmlDomParser;

// https://github.com/WPUserManager/wp-user-manager/issues/298

$I = new AcceptanceTester( $scenario );
$I->amOnPage( '/login' );
$I->see( 'Login' );
$I->fillField( 'username', $_ENV['ADMIN_USERNAME'] );
$I->fillField( 'password', 'bar' );
$I->click('submit_login');
$I->dontSee( '<strong>Error</strong>:' );
