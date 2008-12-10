<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 enc=utf8: */
/*
    MySQL Query Builder
    Copyright © 2005-2009  Alexey Zakhlestin <indeyets@gmail.com>
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

/**
 * Defines set of methods, which must be implemented by any "CONDITION" object
 *
 * @package mysql-query-builder
 */
interface MQB_Condition
{
    /**
     * used for generation of "prepared" SQL-queries. Supposed to be used recursively, and add parameters to the end of $parameters stack
     *
     * @param array $parameters 
     * @return string
     */
    public function getSql(array &$parameters);
}

/**
 * Defines set of methods, which must be implemented by any object, which is used as a Field of result-set row
 *
 * @package mysql-query-builder
 */
interface MQB_Field
{
    /**
     * used for generation of "prepared" SQL-queries. Supposed to be used recursively, and add parameters to the end of $parameters stack
     *
     * @param array $parameters 
     * @return string
     */
    public function getSql(array &$parameters);

    /**
     * returns "alias" name of field
     *
     * @return string
     * @author Jimi Dini
     */
    public function getAlias();
}



/**
 * Represents the table-entity of SQL-query
 *
 * @package mysql-query-builder
 */
class QBTable
{
    private $table_name = null;
    private $db_name = null;

    /**
     * Designated constructor of table-object.
     *
     * @param string $table_name 
     * @param string $db_name 
     */
    public function __construct($table_name, $db_name = null)
    {
        $this->table_name = $table_name;
        $this->db_name = $db_name;
    }

    /**
     * accessor, which returns sql-friendly (escaped) string-representation of table
     *
     * @return string
     */
    public function __toString()
    {
        $res = '';

        if (null !== $this->db_name) {
            $res .= '`'.$this->db_name.'`.';
        }

        $res .= '`'.$this->table_name.'`';

        return $res;
    }

    /**
     * accessor, which returns raw table-name (without database-name)
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table_name;
    }
}

/**
 * generic Operator class. You can't instantiate this directly
 *
 * @package mysql-query-builder
 */
class Operator implements MQB_Condition
{
    private $content = array();
    protected $startSql;
    protected $implodeSql;
    protected $endSql;

    protected function __construct(array $content)
    {
        $this->setContent($content);
    }

    /**
     * Specifies array of MQB_Conditions, which should be used as the content of Operator
     *
     * @param array $content 
     * @return void
     * @throws InvalidArgumentException
     */
    public function setContent(array $content)
    {
        foreach ($content as $c) {
            if (!is_object($c) or !($c instanceof MQB_Condition)) {
                throw new InvalidArgumentException("Operators should be given valid Operators or Conditions as parameters");
            }
        }

        $this->content = $content;
    }

    /**
     * accessor, which returns internal content-array
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    public function getSql(array &$parameters)
    {
        $sqlparts = array();

        foreach ($this->content as $c) {
            $sqlparts[] = $c->getSql($parameters);
        }

        $parts = implode($this->implodeSql, $sqlparts);

        if (empty($parts))
            return '';

        return $this->startSql.$parts.$this->endSql;
    }
}

/**
 * Class, which implements SQLs "NOT()" operator
 *
 * @package mysql-query-builder
 */
class NotOp extends Operator
{
    private $my_content = null;

    /**
     * designated constructor. takes either MQB_Condition or array consisting of the single MQB_Condition
     *
     * @param mixed $content
     * @throws InvalidArgumentException
     */
    public function __construct($content)
    {
        if (is_array($content)) {
            // compatibility with "legacy" API
            if (count($content) != 1)
                throw new InvalidArgumentException("NotOp takes an array of exactly one Condition or Operator");

            $content = $content[0];
        }

        parent::__construct(array($content));
    }

    public function getSql(array &$parameters)
    {
        $content = $this->getContent();
        return 'NOT ('.$content[0]->getSql($parameters).')';
    }
}

/**
 * Class, which implements SQLs "AND" operator
 *
 * @package mysql-query-builder
 */
class AndOp extends Operator
{
    /**
     * Designated constructor.
     * Takes either single parameter — array of MQB_Conditions or several parameters-MQB_Conditions
     *
     * @param string|array $content,...
     */
    public function __construct($content)
    {
        if (func_num_args() > 1)
            parent::__construct(func_get_args());
        else
            parent::__construct($content);

        $this->startSql = "("; 
        $this->implodeSql = " AND ";
        $this->endSql = ")";
    }

    public function getSql(array &$parameters)
    {
        $content = $this->getContent();

        // shortcut
        if (count($content) == 1)
            return $content[0]->getSql($parameters);

        return parent::getSql($parameters);
    }
}

/**
 * Class, which implements SQLs "OR" operator
 *
 * @package mysql-query-builder
 */
class OrOp extends Operator
{
    /**
     * Designated constructor.
     * Takes either single parameter — array of MQB_Conditions or several parameters-MQB_Conditions
     *
     * @param string|array $content,...
     */
    public function __construct($content)
    {
        if (func_num_args() > 1)
            parent::__construct(func_get_args());
        else
            parent::__construct($content);

        $this->startSql = "(";
        $this->implodeSql = " OR ";
        $this->endSql = ")";
    }

    public function getSql(array &$parameters)
    {
        $content = $this->getContent();

        // shortcut
        if (count($content) == 1)
            return $content[0]->getSql($parameters);

        return parent::getSql($parameters);
    }
}

/**
 * Class, which implements SQLs "XOR()" operator
 *
 * @package mysql-query-builder
 */
class XorOp extends Operator
{
    /**
     * Designated constructor.
     * Takes either single parameter — array of MQB_Conditions or several parameters-MQB_Conditions
     *
     * @param string|array $content,...
     */
    public function __construct($content)
    {
        if (func_num_args() > 1)
            parent::__construct(func_get_args());
        else
            parent::__construct($content);

        $this->startSql = "(";
        $this->implodeSql = " XOR ";
        $this->endSql = ")";
    }

    public function getSql(array &$parameters)
    {
        $content = $this->getContent();

        // shortcut
        if (count($content) == 1)
            return $content[0]->getSql($parameters);

        return parent::getSql($parameters);
    }
}

/**
 * Class, which implements generic Condition. Actually, almost any condition can be implemented using it
 *
 * @package mysql-query-builder
 */
class Condition implements MQB_Condition
{
    private $content = array();
    private $validConditions = array("=", "<>", "<", ">", ">=", "<=", "like", "is null", "find_in_set", "and", "or", "xor", "in");
    private $validSingulars = array("is null");

    /**
     * Designated constructor.
     * First parameter should be one of the allowed comparator-strings
     * Second parameter should be some MQB_Field-compliant object
     * Third parameter is the value, which is compared against second-parameter. It should be either scalar-value, or another MQB_Field
     *
     * @param string $comparison 
     * @param MQB_Field $left 
     * @param mixed $right 
     * @throws RangeException, InvalidArgumentException
     */
    public function __construct($comparison, MQB_Field $left, $right = null)
    {
        $comparison = strtolower($comparison);

        if (!in_array($comparison, $this->validConditions))
            throw new RangeException('invalid comparator-function');

        if (!in_array($comparison, $this->validSingulars) and is_scalar($right))
            $right = new Parameter($right);

        if ($comparison == 'in') {
            if (!is_array($right)) {
                throw new InvalidArgumentException('Right-op has to be ARRAY, if comparison is "in"');
            }

            foreach ($right as $value) {
                if (!is_numeric($value)) {
                    throw new InvalidArgumentException('Right-op has to be array consisting of NUMERIC VALUES, if comparison is "in"');
                }
            }
        }

        $this->content = array($comparison, $left, $right);
    }

    public function getSql(array &$parameters)
    {
        $comparison = $this->content[0];
        $leftpart = $this->content[1]->getSql($parameters);

        if ($comparison == 'is null' or ($comparison == '=' and null === $this->content[2])) {
            return $leftpart." IS NULL";
        } elseif ($comparison == '<>' and null === $this->content[2]) {
            return $leftpart." IS NOT NULL";
        } elseif ($comparison == 'in') {
            $rightpart = $this->content[2];

            return $leftpart." IN (".implode(', ', $rightpart).")";
        } else {
            $rightpart = $this->content[2]->getSql($parameters);

            if ($comparison == "find_in_set")
                return $comparison."(".$rightpart.",".$leftpart.")";

            return $leftpart." ".$comparison." ".$rightpart;
        }
    }

    /**
     * accessor which returns comparator
     *
     * @return string
     */
    public function getComparison()
    {
        return $this->content[0];
    }

    /**
     * accessor which returns left-parameter of comparison
     *
     * @return MQB_Field
     */
    public function getLeft()
    {
        return $this->content[1];
    }

    /**
     * accessor which returns right-parameter of comparison
     *
     * @return mixed
     */
    public function getRight()
    {
        return $this->content[2];
    }
}

/**
 * Representation of the Field of N-th Table in Query
 *
 * @package mysql-query-builder
 */
class Field implements MQB_Field
{
    private $name;
    private $table;
    private $alias;

    /**
     * Designated constructor. Used to create representation of 'tN.field as alias' construction, which can be referred from various parts of query
     *
     * @param string $name 
     * @param integer $table 
     * @param string $alias 
     * @throws RangeException
     */
    public function __construct($name, $table = 0, $alias = null)
    {
        if (!$name)
            throw new RangeException('Name of the field is not specified');

        $this->table = $table;
        $this->name = $name;
        $this->alias = $alias;
    }

    public function getSql(array &$parameters, $full = false)
    {
        if (true === $full or null === $this->alias) {
            $res = '`t'.$this->table."`.`".$this->name.'`';

            if (null !== $this->alias) {
                $res .= ' AS `'.$this->alias.'`';
            }
        } else {
            $res = '`'.$this->alias.'`';
        }

        return $res;
    }

    /**
     * accessor for internal "number of table in query" property
     *
     * @return integer
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * accessor for internal "name of the field" property
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * accessor for internal "number of alias" property. returns NULL, if alias is not set
     *
     * @return string|null
     */
    public function getAlias()
    {
        if (null === $this->alias)
            return null;

        return '`'.$this->alias.'`';
    }
}

/**
 * Class, which represents 'table.*' concept
 *
 * @package mysql-query-builder
 */
class AllFields
{
    private $table;

    /**
     * Designated constructor. Takes "number of table in query" as the parameter
     *
     * @param integer $table 
     */
    public function __construct($table = 0)
    {
        $this->table = $table;
    }

    public function getSql(array &$parameters)
    {
        return '`t'.$this->table."`.*";
    }

    /**
     * accessor for internal "number of table in query" property
     *
     * @return integer
     */
    public function getTable()
    {
        return $this->table;
    }
}

/**
 * Class, which represents SQL-Functions used as columns in query
 *
 * @package mysql-query-builder
 */
class SqlFunction implements MQB_Field
{
    private $name;
    private $values;
    private $alias;

    private $validNames = array('substring', 'year', 'month', 'day', 'date');

    /**
     * Designated constructor, which generates representation of '$name($value1, $value2, ... $valueN) as $alias' sql-construct
     * $values can either be literal, MQB_Field or array of literals and MQB_Fields
     *
     * @param string $name 
     * @param mixed $values 
     * @param string $alias 
     * @throws InvalidArgumentException
     */
    public function __construct($name, $values, $alias = null)
    {
        if (!is_string($name) or !in_array($name, $this->validNames))
            throw new InvalidArgumentException('Invalid sql-function: '.$name);

        if (!is_array($values))
            $values = array($values);

        foreach ($values as $v) {
            if (is_object($v) and !($v instanceof MQB_Field))
                throw new InvalidArgumentException("Something wrong passed as a parameter");
        }

        $this->name = $name;
        $this->values = $values;
        $this->alias = $alias;
    }

    public function getSql(array &$parameters)
    {
        $result = strtoupper($this->name)."(";

        $first = true;
        foreach ($this->values as $v) {
            if ($first) {
                $first = false;
            } else {
                $result .= ', ';
            }

            if (is_object($v)) {
                $result .= $v->getSql($parameters);
            } else {
                $result .= $v;
            }
        }

        $result .= ')';

        if (null !== $this->alias) {
            $result .= ' AS '.$this->getAlias();
        }

        return $result;
    }

    /**
     * accessor for internal "number of alias" property. returns NULL, if alias is not set
     *
     * @return string|null
     */
    public function getAlias()
    {
        if (null === $this->alias)
            return null;

        return '`'.$this->alias.'`';
    }

    /**
     * accessor for internal "name of the function" property
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}

/**
 * Class, which represents SQLs aggregate-functions used as columns in query
 *
 * @package mysql-query-builder
 */
class Aggregate implements MQB_Field
{
    private $aggregate;
    private $distinct;
    private $name;
    private $table;
    private $alias;
    private $validAggregates = array("sum", "count", "min", "max", "avg");
    private $field = null;

    /**
     * Creates representation of SQLs aggregate-function.
     * $field can be null, if $aggregate is 'count' — this would result in COUNT(*) query. 
     * If $distinct is set to true, then something like the following will appear: COUNT(DISTINCT `foo`) AS `alias`
     *
     * @param string $aggregate 
     * @param string $field 
     * @param bool $distinct 
     * @param string $alias 
     * @throws RangeException, InvalidArgumentException
     */
    public function __construct($aggregate, $field = null, $distinct=false, $alias=null)
    {
        $aggregate = strtolower($aggregate);

        if (!in_array($aggregate, $this->validAggregates))
            throw new RangeException('Invalid aggregate function: '.$aggregate);

        if (($field instanceof MQB_Field) or (null === $field and $aggregate == 'count')) {
            $this->aggregate = $aggregate;
            $this->distinct = ($distinct === true);
            $this->alias = $alias;
            $this->field = $field;
        } else {
            throw new InvalidArgumentException('field should be MQB_Field');
        }

    }

    public function getSql(array &$parameters, $full = false)
    {
        if (true === $full or null === $this->alias) {
            if (null === $this->field) {
                $field_sql = '*';
            } else {
                $field_sql = $this->field->getSql($parameters);
            }

            if ($this->distinct) {
                $field_sql = 'DISTINCT '.$field_sql;
            }

            $field_sql = strtoupper($this->aggregate).'('.$field_sql.')';

            if (null !== $this->alias) {
                $field_sql .= ' AS `'.$this->alias.'`';
            }
        } else {
            $field_sql = '`'.$this->alias.'`';
        }

        return $field_sql;
    }

    /**
     * accessor for internal "number of alias" property. returns NULL, if alias is not set
     *
     * @return string|null
     */
    public function getAlias()
    {
        if (null === $this->alias)
            return null;

        return '`'.$this->alias.'`';
    }
}

/**
 * Class, which represents literal parameter in SQL-queries.
 * value is never directly used in query, instead, it is put array, which is later used for executing prepared SQL-statement
 *
 * @package mysql-query-builder
 */
class Parameter
{
    private $content;

    /**
     * Creates representation of literal-value, passed as the single parameter
     *
     * @param string|integer|bool|null $content 
     */
    public function __construct($content)
    {
        $this->content = $content;
    }

    public function getSql(array &$parameters)
    {
        $number = count($parameters) + 1;

        $parameters[":p".$number] = $this->content;

        return ":p".$number;
    }

    /**
     * accessor, which returns value of parameter
     *
     * @return mixed
     */
    public function getParameters()
    {
        return $this->content;
    }
}
