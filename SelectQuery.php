<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
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
class SelectQuery extends BasicQuery
{
    private $selects = null;
    private $groupby = null;
    private $havings = null;

    private $distinct = false;

    public function __construct($tables)
    {
        parent::__construct($tables);
        $this->setSelect(array(new AllFields()));
    }

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
                throw new RangeException('Allowed values are objects of the following classes: Field, AllFields, sqlFunction и Aggregate');
        }

        $this->selects = $_selects;
        $this->distinct = $distinct;
        $this->reset();
    }

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

    public function showGroupBy()
    {
        return $this->groupby;
    }

    protected function getSql(&$parameters)
    {
        return $this->getSelect($parameters).
            $this->getFrom($parameters).
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

    public function setHaving($conditions = null)
    {
        if (null === $conditions) {
            $this->havings = null;
        } elseif ($conditions instanceof MQB_Condition) {
            $this->havings = clone $conditions;
        } else {
            throw new InvalidArgumentException('setHaving accepts either NULL or MQB_Condition as a parameter');
        }

        $this->reset();
    }

    protected function getHaving(&$parameters)
    {
        if (null == $this->havings)
            return "";

        return " HAVING ".$this->havings->getSql($parameters);
    }
}
