<?php
/**
 * Bootstrap for wpunit tests.
 * Loads the base test case classes that Codeception won't autoload.
 */

require_once __DIR__ . '/Registration/RegistrationTestCase.php';
require_once __DIR__ . '/WPUMTestCase.php';
require_once __DIR__ . '/Login/LoginTestCase.php';
require_once __DIR__ . '/PasswordRecovery/PasswordRecoveryTestCase.php';
require_once __DIR__ . '/PasswordChange/PasswordChangeTestCase.php';
require_once __DIR__ . '/Privacy/PrivacyTestCase.php';
require_once __DIR__ . '/Fields/FieldsTestCase.php';
require_once __DIR__ . '/Account/AccountTestCase.php';
