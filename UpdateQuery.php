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

class UpdateQuery extends BasicQuery
{
    private $set_fields = null;
    private $set_values = null;
    private $up_limit = null;

    public function __construct($tables)
    {
        parent::__construct($tables);

        $this->set_fields = array();
        $this->set_values = array();
    }

    private function __set($key, $value)
    {
        $this->set_fields[] = new Field($key);
        $this->set_values[] = new Parameter($value);
        $this->reset();
    }

    protected function getSql(&$parameters)
    {
        if (null === $this->set_fields)
            throw new LogicException("Nothing is specified to be set. Can't produce valid MySQL query");

        return $this->getUpdate($parameters).
            $this->getSet($parameters).
            $this->getWhere($parameters).
            $this->getOrderby($parameters).
            $this->getLimit();
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

    public function setValues(array $sets)
    {
        if (count($sets) == 0)
            return;

        $this->set_fields = array();
        $this->set_values = array();

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

    protected function getSet(&$parameters)
    {
        if (null === $this->set_fields)
            return "";

        $sqls = array();
        foreach ($this->set_fields as $i => $value) {
           $sqls[] = $value->getSql($parameters).' = '.$this->set_values[$i]->getSql($parameters);
        }

        return " SET ".implode(", ", $sqls);
    }

    public function setLimit($limit)
    {
        if (count($this->from) != 1) {
            throw new LogicException("setLimit is allowed only in single-table update queries");
        }

        if (!is_numeric($limit) or $limit < 1)
            throw new InvalidArgumentException('setLimit takes as single numeric greater than zero');

        $this->up_limit = (string)$limit;
    }

    public function getLimit()
    {
        return (null == $this->up_limit) ? '' : ' LIMIT '.$this->up_limit;
    }

    public function setOrderby(array $orderlist, array $orderdirectionlist = array())
    {
        if (count($this->from) != 1) {
            throw new LogicException("setOrderby is allowed only in single-table update queries");
        }

        parent::setOrderby($orderlist, $orderdirectionlist);
    }
}
