<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once 'PHPUnit/Framework.php';
require_once "..".DIRECTORY_SEPARATOR."autoload.php";
 
class SelectQueryTest extends PHPUnit_Framework_TestCase
{
    public function testSelectAllFromOneTable()
    {
        $q = new SelectQuery(array('test'));

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0`', $q->sql());
        $this->assertEquals(0, count($q->parameters()));


        $q = new SelectQuery(array('test'));
        $q->setSelect(array(new AllFields()), true);

        $this->assertEquals('SELECT DISTINCT `t0`.* FROM `test` AS `t0`', $q->sql());
        $this->assertEquals(0, count($q->parameters()));


        $q = new SelectQuery(array(new QBTable('test')));

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0`', $q->sql());
        $this->assertEquals(0, count($q->parameters()));
    }

    public function testSelectSomeFromOneTable()
    {
        $q = new SelectQuery(array('test'));
        $q->setWhere(new Condition('=', new Field('somefield'), 35));
        $q->setLimit(10, 2);

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` WHERE `t0`.`somefield` = :p1 LIMIT 10 OFFSET 2', $q->sql());

        $params = $q->parameters();
        $this->assertEquals(35, $params[':p1']);
    }

    public function testNestedConditions()
    {
        $q = new SelectQuery(array('test'));
        $q->setWhere(new AndOp(array(
            new Condition('>', new Field('id'), 12),
            new OrOp(array(
                new Condition('=', new Field('status'), 'demolished'),
                new NotOp(
                    new Condition('<', new Field('age'), 5)
                )
            ))
        )));

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` WHERE (`t0`.`id` > :p1 AND (`t0`.`status` = :p2 OR NOT (`t0`.`age` < :p3)))', $q->sql());
    }

    public function testNotOp()
    {
        try {
            new NotOp(array(
                new Condition('=', new Field('test'), 1),
                new Condition('=', new Field('test'), 2),
            ));
            fail(); // exception should happen
        } catch (InvalidArgumentException $e) {
        }
    }

    public function testInCondition()
    {
        $q = new SelectQuery(array('test'));
        $q->setWhere(new Condition('in', new Field('id'), array(1, 3, 5)));

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` WHERE `t0`.`id` IN (1, 3, 5)', $q->sql());
    }

    public function testSelectSpecificFields()
    {
        $q = new SelectQuery(array('test', 'test2'));
        $q->setSelect(array(new AllFields(), new Field('id', 1)));

        $this->assertEquals('SELECT `t0`.*, `t1`.`id` FROM `test` AS `t0`, `test2` AS `t1`', $q->sql());
    }

    public function testAlias()
    {
        $field1 = new Field('id', 0, 'test');

        $q = new SelectQuery(array('test', 'test2'));
        $q->setSelect(array($field1, new AllFields(1)));
        $q->setWhere(new Condition('=', $field1, '2'));

        $this->assertEquals('SELECT `t0`.`id` AS `test`, `t1`.* FROM `test` AS `t0`, `test2` AS `t1` WHERE `test` = :p1', $q->sql());
    }

    public function testWhereNull()
    {
        $q = new SelectQuery(array('test'));
        $q->setWhere(new AndOp(array(
            new Condition('=', new Field('a'), null),
            new Condition('<>', new Field('b'), null),
        )));

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` WHERE (`t0`.`a` IS NULL AND `t0`.`b` IS NOT NULL)', $q->sql());

        $q->setWhere();
        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0`', $q->sql());
    }

    public function testSelectWrongs()
    {
        try {
            $q = new SelectQuery('test');
            $q->setSelect(array());
            $this->fail("noone is allowed to select nothing!");
        } catch (InvalidArgumentException $e) {
        }

        try {
            $q = new SelectQuery('test');
            $q->setSelect(array('field1'), 'test');
            $this->fail("second params should be boolean!");
        } catch (InvalidArgumentException $e) {
        }

        try {
            $q = new SelectQuery(array(123));
            $this->fail("tables param should be either string or QBTable!");
        } catch (LogicException $e) {
        }

        try {
            $q = new SelectQuery('test');
            $q->setWhere(array());
            $this->fail("condition should be valid");
        } catch (InvalidArgumentException $e) {
        }

        try {
            $q = new SelectQuery('test');
            $q->setHaving('boom');
            $this->fail("condition should be valid");
        } catch (InvalidArgumentException $e) {
        }
    }

    public function testAggregate()
    {
        $q = new SelectQuery('test');
        $q->setSelect(new Aggregate('count', new AllFields(0)));
    }

    public function testGroupBy()
    {
        $group_by = array(new Field('year'));

        $q = new SelectQuery(array('test'));
        $q->setGroupby($group_by);

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` GROUP BY `t0`.`year`', $q->sql());

        $q->setHaving(new Condition('>', new Aggregate('count', new Field('commit')), 20));
        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` GROUP BY `t0`.`year` HAVING COUNT(`t0`.`commit`) > :p1', $q->sql());

        $q->setHaving();
        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` GROUP BY `t0`.`year`', $q->sql());

        $this->assertEquals(var_export($group_by, true), var_export($q->showGroupBy(), true)); // check required by BebopCMS(tm)
    }

    public function testGroupByAggregate()
    {
        $group_by = new Aggregate('count', new Field('user'), true, 'c');
        $field = new Field('very_long_identifier', 0, 'url');

        $q = new SelectQuery('test');
        $q->setSelect(array($group_by, $field));
        $q->setGroupby(array($group_by));
        $q->setOrderBy(array($field));

        $this->assertEquals('SELECT COUNT(DISTINCT `t0`.`user`) AS `c`, `t0`.`very_long_identifier` AS `url` FROM `test` AS `t0` GROUP BY `c` ORDER BY `url` ASC', $q->sql());
    }

    public function testOrderByFunction()
    {
        $f = new SqlFunction('year', new Field('stamp'), 'year');

        $q = new SelectQuery('test');
        $q->setSelect(array($f, new Field('profit')));
        $q->setOrderBy(array($f));

        $this->assertEquals('SELECT YEAR(`t0`.`stamp`) AS `year`, `t0`.`profit` FROM `test` AS `t0` ORDER BY `year` ASC', $q->sql());
    }

    public function testOrderBy()
    {
        $q = new SelectQuery('test');
        $q->setOrderBy(array(new Field('id')), array(true));

        $this->assertEquals('SELECT `t0`.* FROM `test` AS `t0` ORDER BY `t0`.`id` DESC', $q->sql());
    }

    public function testInfo()
    {
        $tbls = array('test1', 'test2', 'test3');
        $q = new SelectQuery($tbls);

        $this->assertEquals(var_export($tbls, true), var_export($q->showTables(), true));

        $condition = new Condition('=', new Field('id'), 1);
        $q->setWhere($condition);

        $this->assertEquals(var_export($condition, true), var_export($q->showConditions(), true));
    }

    public function testMultiSchema()
    {
        $q = new SelectQuery(new QBTable('test', 'db2'));

        $this->assertEquals('SELECT `t0`.* FROM `db2`.`test` AS `t0`', $q->sql());
    }
}
