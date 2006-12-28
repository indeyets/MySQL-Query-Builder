<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
/*
    MySQL Query Builder
    Copyright © 2005-2006  Alexey Zakhlestine <indeyets@gmail.com>
    Copyright © 2005-2006  Konstantin Sedov <kostya.online@gmail.com>

    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
*/

if (version_compare(phpversion(), "5.2.0", '<'))
    die("\nMySQL Query Builder isn't functional for PHP versions earlier than 5.2\n");

interface MQB_Condition
{
    public function getSql(array &$parameters);
}

interface MQB_Field
{
    public function getSql(array &$parameters);
}

class QBTable
{
    private $table_name = null;
    private $db_name = null;

    public function __construct($table_name, $db_name = null)
    {
        $this->table_name = $table_name;
        $this->db_name = $db_name;
    }

    public function __toString()
    {
        $res = '';

        if (null !== $this->db_name) {
            $res .= '`'.$this->db_name.'`.';
        }

        $res .= '`'.$this->table_name.'`';

        return $res;
    }

    public function getTable()
    {
        return $this->table_name;
    }
}

class Operator implements MQB_Condition
{
    private $content = array();
    private $opTypes = array("NotOp", "AndOp", "OrOp", "XorOp", "Condition");
    protected $startSql;
    protected $implodeSql;
    protected $endSql;

    protected function __construct(array $content)
    {
        $this->content = $content;
    }

    public function getSql(array &$parameters)
    {
        $sqlparts = array();

        foreach ($this->content as $c) {
            if (!is_object($c) or !($c instanceof MQB_Condition)) {
                throw new InvalidArgumentException("Operators should be given valid Operators or Conditions as parameters");
            }

            $sqlparts[] = $c->getSql($parameters);
        }

        return $this->startSql.implode($this->implodeSql, $sqlparts).$this->endSql;
    }
}

class NotOp extends Operator
{
    private $my_content = null;

    public function __construct($content)
    {
        if (is_array($content)) {
            // compatibility with "legacy" API
            if (count($content) != 1)
                throw new InvalidArgumentException("NotOp takes an array of exactly one Condition or Operator");

            $content = $content[0];
        }

        if (!is_object($content) or !($content instanceof MQB_Condition)) {
            throw new InvalidArgumentException("Operators should be given valid Operators or Conditions as parameters");
        }

        $this->my_content = $content;
    }

    public function getSql(array &$parameters)
    {
        return 'NOT ('.$this->my_content->getSql($parameters).')';
    }
}

class AndOp extends Operator
{
    public function __construct(array $content)
    {
        parent::__construct($content);

        $this->startSql = "("; 
        $this->implodeSql = " AND ";
        $this->endSql = ")";
    }
}

class OrOp extends Operator
{
    public function __construct(array $content)
    {
        parent::__construct($content);

        $this->startSql = "(";
        $this->implodeSql = " OR ";
        $this->endSql = ")";
    }
}

class XorOp extends Operator
{
    public function __construct(array $content)
    {
        parent::__construct($content);
        $this->startSql="( ";
        $this->implodeSql=" XOR ";
        $this->endSql=" )";
    }
}

class Condition implements MQB_Condition
{
    private $content=array();
    private $validConditions=array("=","<>","<",">",">=","<=","like","is null","find_in_set","and","or","xor");
    private $validSingulars=array("is null");

    public function __construct($comparison, $left, $right=null)
    {
        $comparison = strtolower($comparison);

        if (!in_array($comparison, $this->validConditions))
            throw new RangeException('Недопустимая функция сравнения');

        if (!is_object($left))
            throw new InvalidArgumentException('Первый параметр для сравнения может быть только объектом');

        if (!in_array($comparison, $this->validSingulars) and !is_object($right))
            $right = new Parameter($right);

        $this->content=array($comparison,$left,$right);
    }

    public function getSql(array &$parameters)
    {
        $comparison = $this->content[0];
        $leftpart = $this->content[1]->getSql($parameters);

        if ($comparison == 'is null'/*in_array($comparison, $this->validSingulars)*/) {
            return $leftpart." ".$comparison;
        } else {
            $rightpart = $this->content[2]->getSql($parameters);
            if ($comparison == "find_in_set")
                return $comparison."(".$rightpart.",".$leftpart.")";
            return $leftpart." ".$comparison." ".$rightpart;
        }
    }
    public function getComparison()
    {
        return $this->content[0];
    }
    public function getLeft()
    {
        return $this->content[1];
    }
    public function getRight()
    {
        return $this->content[2];
    }
}

class Field implements MQB_Field
{
    private $name;
    private $table;

    public function __construct($name, $table=0)
    {
        if (!$name)
            throw new RangeException('Не указано имя поля/столбца');
        $this->table=$table;
        $this->name=$name;
    }

    public function getSql(array &$parameters)
    {
        return '`t'.$this->table."`.`".$this->name.'`';
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getName()
    {
        return $this->name;
    }
}

class sqlFunction implements MQB_Field
{
    private $field;
    private $name;
    private $values;
    private $validNames = array('substring', 'year', 'month', 'day', 'date');

    public function __construct($name, Field $field, array $values = null)
    {
        if (!is_string($name) or !in_array($name, $this->validNames))
            throw new RangeException('Недопустимое имя функции');

        $this->field = $field;
        $this->name = $name;
        $this->values = $values;
    }

    public function getSql(array &$parameters)
    {
        $result = $this->name."(".$this->field->getSql($parameters);

        if (null !== $this->values) {
            foreach ($this->values as $v) {
                if (is_object($v)) {
                    if (method_exists($v,'getSql')) {
                        $result .= ",".$v->getSql($parameters);
                    }
                } else {
                    $result .= ",".$v;
                }
            }
        }

        return $result.")";
    }

    public function getField()
    {
        return $this->field;
    }

    public function getName()
    {
        return $this->name;
    }
}

class Aggregate implements MQB_Field
{
    private $aggregate;
    private $name;
    private $table;
    private $validAggregates = array("sum", "count", "min", "max", "avg");
    private $field = null;

    public function __construct($aggregate, Field $field=null)
    {
        if (!in_array($aggregate, $this->validAggregates))
            throw new RangeException('Недопустимая аггрегирующая функция');

        $this->aggregate = $aggregate;

        if (null !== $field)
            $this->field = $field;
    }

    public function getSql(array &$parameters)
    {
        $field_sql = (null === $this->field ? '*' : $this->field->getSql($parameters));

        return $this->aggregate."(".$field_sql.')';
    }
}

class Parameter
{
    private $content;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getSql(array &$parameters)
    {
        $this->number = count($parameters) + 1;
        $parameters[":p".$this->number] = $this->content;
        return ":p".$this->number;
    }

    public function getParameters()
    {
        return $this->content;
    }

    public function getNumber()
    {
        return $this->number;
    }
}
