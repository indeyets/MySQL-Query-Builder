<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 enc=utf8: */
/**
 * @author Alexey Zakhlestin
 * @package mysql-query-builder
 **/
/*
    MySQL Query Builder
    Copyright © 2005-2007  Alexey Zakhlestin <indeyets@gmail.com>
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
 * This class contains all the common logic shared by other query-classes
 *
 * @package mysql-query-builder
 * @author Alexey Zakhlestin
 */
class BasicQuery
{
    private $limit = null;
    private $conditions = null;
    private $parameters;
    private $sql = null;
    private $orderby;
    private $orderdirection;

    /**
     * contains QBTable objects related to current query
     *
     * @var array
     */
    protected $from = array();

    /**
     * Constructor provides common logic (all queries are done on tables), but does not direct instantiation of BasicQuery
     *
     * @param mixed $tables 
     */
    protected function __construct($tables)
    {
        $this->setTables($tables);
    }

    /**
     * Sets which table(s) the query will be applied to
     *
     * @param mixed $tables Can be either string, QBTable instance or array of strings/QBTables
     * @return void
     * @throws InvalidArgumentException, LogicException
     */
    public function setTables($tables)
    {
        if (is_string($tables) or $tables instanceof QBTable)
            $tables = array($tables);

        if (!is_array($tables))
            throw new InvalidArgumentException('table(s) should be specified as a string, or array of strings');

        if (count($tables) == 0)
            throw new InvalidArgumentException('there were no tables, specified');

        $this->from = array();
        foreach ($tables as $table) {
            if (is_string($table)) {
                $this->from[] = new QBTable($table);
            } elseif ($table instanceof QBTable) {
                $this->from[] = $table;
            } else {
                throw new LogicException("Invalid object is provided as a table");
            }
        }

        $this->reset();
    }

    /**
     * Sets where-condition, which will be applied to query
     * The most typical objects to use as parameters are Condition and AndOp
     *
     * @param MQB_Condition $conditions 
     * @return void
     * @author Jimi Dini
     */
    public function setWhere(MQB_Condition $conditions = null)
    {
        if (null === $conditions) {
            $this->conditions = null;
        } elseif ($conditions instanceof MQB_Condition) {
            $this->conditions = clone $conditions;
        }

        $this->reset();
    }

    /**
     * setup "ORDER BY" clause of Query.
     * $orderlist is supposed to be array of objects implementing MQB_Field (most-probably, Field objects).
     * $orderdirectionlist is supposed to be array of booleans, where TRUE means DESC and FALSE means ASC.
     * if number of elements of $orderdirectionlist is smaller that number of elements of $orderlist array, then ASC is applied to the tail-objects
     *
     * @param array $orderlist 
     * @param array $orderdirectionlist 
     * @return void
     * @throws InvalidArgumentException
     */
    public function setOrderby(array $orderlist, array $orderdirectionlist = array())
    {
        foreach ($orderlist as $field)
            if (!($field instanceof MQB_Field))
                throw new InvalidArgumentException('Only object implementing MQB_Field can be used in setOrderBy');

        $this->orderby = $orderlist;
        $this->orderdirection = $orderdirectionlist;

        $this->reset();
    }

    /**
     * setup "LIMIT" clause of Query.
     *
     * @param integer $limit 
     * @param integer $offset 
     * @return void
     * @throws InvalidArgumentException
     */
    public function setLimit($limit, $offset=0)
    {
        if (!is_numeric($limit) or !is_numeric($offset))
            throw new InvalidArgumentException('Limit should be specified using numerics');

        $this->limit = array($limit, $offset);
    }

    /**
     * accessor, which returns array of table-names used in Query.
     *
     * @return array
     */
    public function showTables()
    {
        $res = array();
        foreach ($this->from as $table) {
            $res[] = $table->getTable();
        }

        return $res;
    }

    /**
     * accessor, which returns current-querys condition
     *
     * @return MQB_Condition
     */
    public function showConditions()
    {
        return $this->conditions;
    }

    // internal stuff

    /**
     * This method should be overridden by descendents
     *
     * @param array $parameters 
     * @return void
     * @throws LogicException
     */
    protected function getSql(array &$parameters)
    {
        throw new LogicException();
    }

    /**
     * Returns "FROM" clause which can be used in various queries
     *
     * @param array $parameters 
     * @return void
     */
    protected function getFrom(array &$parameters)
    {
        $froms = array();
        for ($i = 0; $i < count($this->from); $i++) {
            $froms[] = $this->from[$i]->__toString().' AS `t'.$i.'`';
        }

        $sql = ' FROM '.implode(", ", $froms);

        return $sql;
    }

    /**
     * Returns "WHERE" clause which can be used in various queries
     *
     * @param array $parameters 
     * @return void
     */
    protected function getWhere(array &$parameters)
    {
        if (null === $this->conditions)
            return "";

        $sql = $this->conditions->getSql($parameters);

        if (empty($sql))
            return "";

        return " WHERE ".$sql;
    }

    /**
     * Returns "ORDER BY" clause which can be used in various queries
     *
     * @param array $parameters 
     * @return void
     */
    protected function getOrderby(array &$parameters)
    {
        if (!$this->orderby || !is_array($this->orderby))
            return "";

        foreach ($this->orderby as $i => $field) {
            if (array_key_exists($i, $this->orderdirection) && $this->orderdirection[$i])
                $direction = ' DESC';
            else
                $direction = ' ASC';

            if (null !== $alias = $field->getAlias())
                $sqls[] = $alias.$direction;
            else
                $sqls[] = $field->getSql($parameters).$direction;
        }

        return " ORDER BY ".implode(", ", $sqls);
    }

    /**
     * Returns "LIMIT" clause which can be used in various queries
     *
     * @param array $parameters 
     * @return void
     */
    protected function getLimit(array &$parameters)
    {
        if (null === $this->limit)
            return "";

        return " LIMIT ".$this->limit[0].' OFFSET '.$this->limit[1];
    }

    /**
     * resets internal cache-structures, which are used for generation of sql-string and parameters-array
     *
     * @return void
     */
    protected function reset()
    {
        $this->parameters = array();
        $this->sql = null;
    }

    /**
     * rebuilds (if needed) and returns SQL-string, which can be used for "prepared" query
     *
     * @return string
     */
    public function sql()
    {
        if (null === $this->sql) {
            $this->parameters = array();
            $this->sql = $this->getSql($this->parameters);
        }

        return $this->sql;
    }

    /**
     * returns array of parameters, which can be used with SQL-string from ->sql() method.
     * WARNING: this method does not rebuild SQL-Query. Be sure to call ->sql() before using it.
     *
     * @return array
     * @author Jimi Dini
     */
    public function parameters()
    {
        if (null === $this->sql) {
            throw new LogicException('->sql() method should be called, before calling ->parameters() method');
        }

        return $this->parameters;
    }
}
