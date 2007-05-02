<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once "..".DIRECTORY_SEPARATOR."autoload.php";
require_once 'PHPUnit/Framework.php';
 
class DeleteQueryTest extends PHPUnit_Framework_TestCase
{
    public function testSimple()
    {
        $q = new DeleteQuery(array('test'));

        $this->assertEquals('DELETE FROM `test`', $q->sql()); // aliases are NOT supported in one-table delete queries
    }

    public function testOneOfMultiple()
    {
        $q = new DeleteQuery(array('test', 'test2', 'test3'));

        $this->assertEquals('DELETE FROM `t0` USING `test` AS `t0`, `test2` AS `t1`, `test3` AS `t2`', $q->sql());
    }

    public function testSeveralOfMultiple()
    {
        $q = new DeleteQuery(array('test', 'test2', 'test3'), array(0, 2));

        $this->assertEquals('DELETE FROM `t0`, `t2` USING `test` AS `t0`, `test2` AS `t1`, `test3` AS `t2`', $q->sql());
    }

    public function testWhere()
    {
        $q = new DeleteQuery(array('test'));
        $q->setWhere(new AndOp(array(
            new Condition('=', new Field('group'), 'test'),
            new Condition('=', new Field('author'), null)
        )));

        $this->assertEquals('DELETE FROM `test` WHERE (`test`.`group` = :p1 AND `test`.`author` IS NULL)', $q->sql());
    }

    public function testOrderLimit()
    {
        $q = new DeleteQuery(array('test'));
        $q->setLimit(10);
        $q->setOrderBy(array(new Field('field1')));

        $this->assertEquals('DELETE FROM `test` ORDER BY `test`.`field1` ASC LIMIT 10', $q->sql());
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
