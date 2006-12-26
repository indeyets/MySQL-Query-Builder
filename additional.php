<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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

class Operator
{
    private $content = array();
    private $opTypes = array("NotOp","AndOp","OrOp","XorOp","Condition");
    protected $startSql;
    protected $implodeSql;
    protected $endSql;

    public function __construct(array $content)
    {
        $this->content = $content;
    }

    public function getSql(&$parameters)
    {
        $sqlparts = array();
        foreach ($this->content as $c) {
            if (is_object($c) and ($c instanceof Operator or $c instanceof Condition)) {
                $sqlparts[] = $c->getSql($parameters);
            }
        }

        return $this->startSql.implode($this->implodeSql, $sqlparts).$this->endSql;
    }
}

class NotOp extends Operator
{
    public function __construct(array $content)
    {
        parent::__construct($content);
        $this->startSql=" NOT (";
        $this->implodeSql="";
        $this->endSql=")";
    }
}
class AndOp extends Operator
{
    public function __construct(array $content)
    {
        parent::__construct($content);
        $this->startSql="( ";
        $this->implodeSql=" AND ";
        $this->endSql=" )";
    }
}
class OrOp extends Operator
{
    public function __construct(array $content)
    {
        parent::__construct($content);
        $this->startSql="( ";
        $this->implodeSql=" OR ";
        $this->endSql=" )";
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

class Condition
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

    public function getSql(&$parameters)
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

class Field
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
    public function getSql(&$parameters)
    {
        return "t".$this->table.".`".$this->name.'`';
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

class sqlFunction
{
    private $field;
    private $name;
    private $values;
    private $validNames=array('substring','year','month','day','date');
    public function __construct($name,$field,array $values = null)
    {
        if (!$name || !in_array($name,$this->validNames))
            throw new RangeException('Недопустимое имя функции');
        if (!$field || !is_object($field))
            throw new RangeException('Первый параметр может быть только объектом');
        $this->field=$field;
        $this->name=$name;
        $this->values=$values;
    }
    public function getSql(&$parameters)
    {
        $result=$this->name."(".$this->field->getSql($parameters);
        if (!is_null($this->values)) foreach ($this->values as $v) {
            if (is_object($v)) {
                if (method_exists($v,'getSql')) {
                    $result.=",".$v->getSql($parameters);
                }
            } else {
                $result.=",".$v;
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

class Aggregate
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
    public function getSql(&$parameters)
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
    public function getSql(&$parameters)
    {
        $this->number=(count($parameters)+1);
        $parameters[":p".$this->number]=$this->content;
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
