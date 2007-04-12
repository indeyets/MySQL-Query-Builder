<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
require_once "..".DIRECTORY_SEPARATOR."autoload.php";
require_once 'PHPUnit/Framework.php';
 
class InsertQueryTest extends PHPUnit_Framework_TestCase
{
    public function testBasic()
    {
        $q = new InsertQuery(array('test'));
        $q->setValues(array(
            'field1' => 'value1',
            'field2' => 'value2'
        ));

        $this->assertEquals('INSERT INTO `test` (`field1`, `field2`) VALUES (:p1, :p2)', $q->sql());
        $params = $q->parameters();

        $this->assertEquals('value1', $params[':p1']);
        $this->assertEquals('value2', $params[':p2']);

        // shorthand
        $q = new InsertQuery('test');
        $q->field1 = 'value1';
        $q->field2 = 'value2';

        $this->assertEquals('INSERT INTO `test` (`field1`, `field2`) VALUES (:p1, :p2)', $q->sql());
        $params = $q->parameters();

        $this->assertEquals('value1', $params[':p1']);
        $this->assertEquals('value2', $params[':p2']);
    }

    public function testMultitable()
    {
        try {
            $q = new InsertQuery(array('test', 'test2'));
            $this->assertEquals(true, false);
        } catch (InvalidArgumentException $e) {
        }
    }

    public function testOnDuplicate()
    {
        $q = new InsertQuery(array('test'), true);
        $q->setValues(array(
            'id' => '35',
            'field1' => 'value1',
            'field2' => 'value2'
        ));

        $this->assertEquals('INSERT INTO `test` (`id`, `field1`, `field2`) VALUES (:p1, :p2, :p3) ON DUPLICATE KEY UPDATE `field1` = :p4, `field2` = :p5', $q->sql());
        $params = $q->parameters();

        $this->assertEquals('35', $params[':p1']);
        $this->assertEquals('value1', $params[':p2']);
        $this->assertEquals('value2', $params[':p3']);
        $this->assertEquals('value1', $params[':p4']);
        $this->assertEquals('value2', $params[':p5']);
    }
}
