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
 * This class contains logic of "SELECT" queries
 *
 * @package mysql-query-builder
 * @author Alexey Zakhlestin
 */
class SelectQuery extends BasicQuery
{
    private $selects = null;
    private $groupby = null;
    private $havings = null;
    private $indices = null;

    private $distinct = false;

    /**
     * Creates new SELECT-query object.
     * By default, it is equivalent of "SELECT t0.* FROM t0, t1, t2, tN", where t0-tN are tables given to this constructor
     *
     * @param mixed $tables 
     */
    public function __construct($tables)
    {
        parent::__construct($tables);
        $this->setSelect(array(new AllFields()));
    }

    /**
     * Specifies, which columns should be selected.
     * $_select can be instance of AllFields, MQB_Field or array of such instances.
     * If $distinct is set to TRUE, 'SELECT DISTINCT …' query shall be used
     *
     * @param mixed $_selects 
     * @param bool $distinct 
     * @return void
     * @throws InvalidArgumentException, RangeException
     */
    public function setSelect($_selects, $distinct = false)
    {
        if (!is_array($_selects)) {
            $_selects = array($_selects);
        }

        if (count($_selects) == 0) {
            throw new InvalidArgumentException('Nothing to select');
        }

        if (!is_bool($distinct)) {
            throw new InvalidArgumentException('"distinct" parameter should be boolean');
        }

        foreach ($_selects as $s) {
            if (!($s instanceof MQB_Field) and !($s instanceof AllFields))
                throw new RangeException('Allowed values are objects of the following classes: Field, AllFields, sqlFunction and Aggregate');
        }

        $this->selects = $_selects;
        $this->distinct = $distinct;
        $this->reset();
    }

    /**
     * Specifies, which index(es) should be preferred for the first table of query
     * $indices should be either string or array of strings
     *
     * @param mixed $indices 
     * @return void
     */
    public function setIndices($indices)
    {
        if (!is_array($indices)) {
            $indices = array($indices);
        }

        $this->indices = $indices;
    }

    /**
     * Specifies, if the query should use "GROUP BY" clause.
     * MQB_Field of array of MQB_Fields is allowed as parameter
     *
     * @param mixed $orderlist 
     * @return void
     * @throws InvalidArgumentException
     */
    public function setGroupby($orderlist)
    {
        if (!is_array($orderlist))
            $orderlist = array($orderlist);

        foreach ($orderlist as $field)
            if (!($field instanceof MQB_Field))
                throw new InvalidArgumentException('setGroupBy takes only [array of] MQB_Fields as parameter');

        $this->groupby = $orderlist;
        $this->reset();
    }

    /**
     * Accessor, which returns internal "GROUP BY" array
     *
     * @return array
     */
    public function showGroupBy()
    {
        return $this->groupby;
    }

    protected function getSql(&$parameters)
    {
        return $this->getSelect($parameters).
            $this->getFrom($parameters).
            // $this->getIndices().
            $this->getWhere($parameters).
            $this->getGroupby($parameters).
            $this->getHaving($parameters).
            $this->getOrderby($parameters).
            $this->getLimit($parameters);
    }

    private function getSelect(&$parameters)
    {
        $res = 'SELECT ';

        if (true === $this->distinct) {
            $res .= 'DISTINCT ';
        }

        $sqls = array();
        foreach ($this->selects as $s) {
            $sqls[] = $s->getSql($parameters, true);
        }

        return $res.implode(", ", $sqls);
    }

    protected function getFrom(&$parameters)
    {
        $froms = array();
        for ($i = 0; $i < count($this->from); $i++) {
            $_str = $this->from[$i]->__toString().' AS `t'.$i.'`';
            if (0 == $i)
                $_str .= $this->getIndices();
            $froms[] = $_str;
        }

        $sql = ' FROM '.implode(", ", $froms);

        return $sql;
    }

    private function getGroupby(&$parameters)
    {
        if ($this->groupby === null)
            return "";

        foreach ($this->groupby as $groupby) {
            if (null !== $alias = $groupby->getAlias())
                $sqls[] = $alias;
            else
                $sqls[] = $groupby->getSql($parameters);
        }

        return " GROUP BY ".implode(", ", $sqls);
    }

    /**
     * Specifies "HAVING" clause of query
     *
     * @param MQB_Condition $conditions 
     * @return void
     * @author Jimi Dini
     */
    public function setHaving(MQB_Condition $conditions = null)
    {
        if (null === $conditions) {
            $this->havings = null;
        } elseif ($conditions instanceof MQB_Condition) {
            $this->havings = clone $conditions;
        }

        $this->reset();
    }

    protected function getHaving(&$parameters)
    {
        if (null == $this->havings)
            return "";

        return " HAVING ".$this->havings->getSql($parameters);
    }

    protected function getIndices()
    {
        if (null === $this->indices)
            return '';

        $res = ' USE INDEX (';
        $first = true;
        foreach ($this->indices as $idx) {
            if (true === $first)
                $first = false;
            else
                $res .= ', ';

            $res .= '`'.$idx.'`';
        }
        $res .= ')';

        return $res;
    }
}
