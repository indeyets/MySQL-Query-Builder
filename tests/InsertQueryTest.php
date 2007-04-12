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
}