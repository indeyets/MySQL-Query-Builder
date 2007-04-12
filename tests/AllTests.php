<?php
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'AllTests::main');
}

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require 'SelectQueryTest.php';
require 'UpdateQueryTest.php';
require 'DeleteQueryTest.php';
require 'InsertQueryTest.php';
require 'OtherTest.php';

class AllTests
{
    public static function main()
    {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('PHPUnit Framework');

        $suite->addTestSuite('SelectQueryTest');
        $suite->addTestSuite('UpdateQueryTest');
        $suite->addTestSuite('DeleteQueryTest');
        $suite->addTestSuite('InsertQueryTest');
        $suite->addTestSuite('OtherTest');

        return $suite;
    }
}

if (PHPUnit_MAIN_METHOD == 'AllTests::main') {
    AllTests::main();
}
