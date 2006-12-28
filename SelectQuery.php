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

    public function __construct(array $tables)
    {
        parent::__construct($tables);
    }

    public function setSelect (array $_selects)
    {
        $selects = array();

        foreach ($_selects as $s) {
            if (!($s instanceof Field) and !($s instanceof Aggregate) and !($s instanceof sqlFunction))
                throw new RangeException('Допустимые значения - объекты классов Field, sqlFunction и Aggregate');

            $this->selects[] = $s;
        }

        if (count($selects) > 0) {
            $this->selects = $selects;
            $this->reset();     
        }
    }

    public function setGroupby(array $orderlist)
    {
        foreach ($orderlist as $field)
            if (!($field instanceof Field) && !($field instanceof Aggregate) && !($field instanceof sqlFunction))
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
        if (null !== $this->selects) {
            $sqls = array();
            foreach ($this->selects as $s) {
                $sqls[] = $s->getSql($parameters);
            }
            return "SELECT ".implode(", ", $sqls);
        }

        return 'SELECT `t0`.*';
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
