<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require "..".DIRECTORY_SEPARATOR."autoload.php";
require_once 'PHPUnit/Framework.php';
 
class DeleteQueryTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $q = new DeleteQuery(array('test'));

        $this->assertEquals('DELETE FROM `test` AS `t0`', $q->sql());
    }

    public function testOneOfMultiple()
    {
        $q = new DeleteQuery(array('test', 'test2', 'test3'));

        $this->assertEquals('DELETE FROM `t0` USING `test` AS `t0`, `test2` AS `t1`, `test3` AS `t2`', $q->sql());
    }

    public function testOrderLimit()
    {
        $q = new DeleteQuery(array('test'));
        $q->setLimit(10);
        $q->setOrderBy(array(new Field('field1')));

        $this->assertEquals('DELETE FROM `test` AS `t0` ORDER BY `t0`.`field1` ASC LIMIT 10', $q->sql());
    }

    public function testOrderLimitOnMultiple()
    {
        try {
            $q = new DeleteQuery(array('test', 'test2', 'test3'));
            $q->setLimit(10);
            $this->assertEquals(true, false);
        } catch (LogicException $e) {
        }

        try {
            $q = new DeleteQuery(array('test', 'test2', 'test3'));
            $q->setOrderBy(array(new Field('field1')));
            $this->assertEquals(true, false);
        } catch (LogicException $e) {
        }
    }
}
