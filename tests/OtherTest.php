<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once 'PHPUnit/Framework.php';
require_once "..".DIRECTORY_SEPARATOR."autoload.php";
 
class OtherTest extends PHPUnit_Framework_TestCase
{
    public function testOperator()
    {
        try {
            $op = new AndOp(array(new Field('qq')));
            $this->fail("invalid input accepted");
        } catch (InvalidArgumentException $e) {
        }

        try {
            $op = new NotOp(array(new Field('qq')));
            $this->fail("invalid input accepted");
        } catch (InvalidArgumentException $e) {
        }

        $params = array();
        $op = new NotOp(array(new Condition('=', new Field('test'), '1')));
        $this->assertEquals('NOT (`t0`.`test` = :p1)', $op->getSql($params));


        $params = array();
        $op = new XorOp(array(new Condition('=', new Field('test'), '1'), new Condition('=', new Field('test'), '2')));
        $this->assertEquals('(`t0`.`test` = :p1 XOR `t0`.`test` = :p2)', $op->getSql($params));
    }

    public function testCondition()
    {
        $params = array();

        $f = new Field('test');
        $arr = array(1,2,3,4,5);
        $c = new Condition('in', $f, $arr);

        $this->assertEquals('`t0`.`test` IN (1, 2, 3, 4, 5)', $c->getSql($params));
        $this->assertEquals('in', $c->getComparison());
        $this->assertEquals(var_export($f, true), var_export($c->getLeft(), true));
        $this->assertEquals(var_export($arr, true), var_export($c->getRight(), true));


        try {
            $c = new Condition('in', new Field('test'), 'error');
            $this->fail('second parameter shoud be array-only, but string passed');
        } catch (InvalidArgumentException $e) {
        }

        try {
            $c = new Condition('in', new Field('test'), array('a'));
            $this->fail('second parameter shoud be array of integets, but array of strings passed');
        } catch (InvalidArgumentException $e) {
        }
    }

    public function testField()
    {
        $f = new Field('test', 1);
        $this->assertEquals('test', $f->getName());
        $this->assertEquals(1, $f->getTable());

        $f = new AllFields();
        $this->assertEquals(0, $f->getTable());

        $f = new AllFields(2);
        $this->assertEquals(2, $f->getTable());
    }

    public function testSqlFunctions()
    {
        $f = new SqlFunction('substring', array(new Field('name'), 5, 2));

        $params = array();
        $this->assertEquals('SUBSTRING(`t0`.`name`, 5, 2)', $f->getSql($params));
        $this->assertEquals('substring', $f->getName());

        try {
            $f = new SqlFunction('there_is_no_such_function', new Field('name'));
            $this->fail();
        } catch (InvalidArgumentException $e) {
        }

        try {
            $f = new SqlFunction('substring', new AndOp(array()));
            $this->fail('wrong value accepted');
        } catch (InvalidArgumentException $e) {
        }
    }

    public function testParameter()
    {
        $p = new Parameter(123);
        $this->assertEquals(123, $p->getParameters());
    }
}
