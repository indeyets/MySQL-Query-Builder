<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once 'PHPUnit/Framework.php';
require_once "..".DIRECTORY_SEPARATOR."autoload.php";
 
class SelectQueryTest extends PHPUnit_Framework_TestCase
{
    public function testSelectAllFromOneTable()
    {
        $q = new SelectQuery(array('test'));
        $this->assertEquals('SELECT t0.* FROM `test` as t0', $q->sql());
    }
}
