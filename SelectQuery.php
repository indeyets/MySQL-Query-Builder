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
class SelectQuery extends BasicQuery
{
    private $selects = null;
    private $groupby = null;

    private $distinct = false;

    public function __construct(array $tables)
    {
        parent::__construct($tables);
    }

    public function setSelect(array $_selects, $distinct = false)
    {
        if (count($_selects) == 0) {
            throw new InvalidArgumentException('Nothing to select');
        }

        if (!is_bool($distinct)) {
            throw new InvalidArgumentException('"distinct" parameter should be boolean');
        }

        foreach ($_selects as $s) {
            if (!($s instanceof MQB_Field))
                throw new RangeException('Допустимые значения - объекты классов Field, sqlFunction и Aggregate');
        }

        $this->selects = $_selects;
        $this->distinct = $distinct;
        $this->reset();
    }

    public function setGroupby(array $orderlist)
    {
        foreach ($orderlist as $field)
            if (!($field instanceof MQB_Field))
                throw new InvalidArgumentException('Допускается только массив объектов типа Field');

        $this->groupby = $orderlist;
        $this->reset();
    }

    public function showGroupby()
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

        if (null === $this->selects) {
            return $res.'`t0`.*';
        }

        $sqls = array();
        foreach ($this->selects as $s) {
            $sqls[] = $s->getSql($parameters);
        }

        return $res.implode(", ", $sqls);
    }

    private function getGroupby(&$parameters)
    {
        if ($this->groupby === null)
            return "";

        foreach ($this->groupby as $groupby) {
            $sqls[] = $groupby->getSql($parameters);
        }

        return " GROUP BY ".implode(", ", $sqls);
    }
}
