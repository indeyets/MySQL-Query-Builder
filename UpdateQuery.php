<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4 enc=utf8: */
/**
 * @author Alexey Zakhlestin
 * @package mysql-query-builder
 **/
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
 * This class contains logic of "UPDATE" queries
 *
 * @package mysql-query-builder
 * @author Alexey Zakhlestin
 */
class UpdateQuery extends BasicQuery
{
    private $set_fields = null;
    private $set_values = null;
    private $up_limit = null;

    /**
     * Creates new UPDATE-query object.
     * By default, it is equivalent of "UPDATE t0, t1, t2, tN SET …", where t0-tN are tables given to this constructor
     * Be sure to specify some fields to be updated, or query will fail to be generated
     *
     * @param mixed $tables 
     */
    public function __construct($tables)
    {
        parent::__construct($tables);

        $this->set_fields = array();
        $this->set_values = array();
    }

    /**
     * magic accessor, which lets setting parts of "SET …" clause with simple "$obj->field = 'value';" statements
     *
     * @param string $key 
     * @param mixed $value 
     * @return void
     */
    public function __set($key, $value)
    {
        $this->set_fields[] = new Field($key);
        $this->set_values[] = new Parameter($value);
        $this->reset();
    }

    /**
     * sets "SET …" clause of query to the new value. Array is supposed to be in one of the following formats: 
     * 1) [field_name => value, field2 => value2, …] 
     * 2) [[field_name, value], [field2, value2], …] 
     *
     * @param array $sets 
     * @return void
     */
    public function setValues(array $sets)
    {
        $this->set_fields = array();
        $this->set_values = array();

        if (count($sets) == 0)
            return;

        foreach ($sets as $set => $value) {
            if (is_array($value)) {
                // nested arrays. $value[0] is Field, $value[1] is Parameter
                if (is_string($value[0]))
                    $value[0] = new Field($value[0]);

                $this->set_fields[] = $value[0];
                $this->set_values[] = new Parameter($value[1]);
            } else {
                // key-value pairs
                $this->set_fields[] = new Field($set);
                $this->set_values[] = new Parameter($value);
            }
        }

        $this->reset();
    }

    /**
     * Sets maximum number of rows, the UPDATE query will be applied to.
     * MySQL does not allow to specify offset, so, it is just a single number
     *
     * @param integer $limit 
     * @return void
     * @throws LogicException, InvalidArgumentException
     */
    public function setLimit($limit)
    {
        if (count($this->from) != 1) {
            throw new LogicException("setLimit is allowed only in single-table update queries");
        }

        if (!is_numeric($limit) or $limit < 1)
            throw new InvalidArgumentException('setLimit takes as single numeric greater than zero');

        $this->up_limit = (string)$limit;
    }

    /**
     * wrapper around BasicQuery::setOrderBy, which additionally checks if it is allowed, to apply order to the query. 
     * it is allowed only for single-table queries
     *
     * @param array $orderlist 
     * @param array $orderdirectionlist 
     * @return void
     * @throws LogicException
     */
    public function setOrderby(array $orderlist, array $orderdirectionlist = array())
    {
        if (count($this->from) != 1) {
            throw new LogicException("setOrderby is allowed only in single-table update queries");
        }

        parent::setOrderby($orderlist, $orderdirectionlist);
    }

    protected function getSql(array &$parameters)
    {
        if (null === $this->set_fields)
            throw new LogicException("Nothing is specified to be set. Can't produce valid MySQL query");

        return $this->getUpdate($parameters).
            $this->getSet($parameters).
            $this->getWhere($parameters).
            $this->getOrderby($parameters).
            $this->getLimit();
    }

    protected function getLimit()
    {
        return (null == $this->up_limit) ? '' : ' LIMIT '.$this->up_limit;
    }

    private function getUpdate(&$parameters)
    {
        $sql = 'UPDATE ';

        for ($i = 0; $i < count($this->from); $i++) {
            if ($i > 0)
                $sql .= ', ';
            $sql .= $this->from[$i]->__toString().' AS `t'.$i.'`';
        }

        return $sql;
    }

    protected function getSet(&$parameters)
    {
        if (null === $this->set_fields or 0 == count($this->set_fields))
            throw new LogicException("Empty update-queries are forbidden");

        $sqls = array();
        foreach ($this->set_fields as $i => $value) {
           $sqls[] = $value->getSql($parameters).' = '.$this->set_values[$i]->getSql($parameters);
        }

        return " SET ".implode(", ", $sqls);
    }
}
