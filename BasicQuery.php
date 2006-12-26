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

class BasicQuery
{
    private $limit;
    private $conditions = null;
    private $parameters;
    private $sql = false;
    protected $from = array();
    private $orderby;
    private $orderdirection;
    private $havings;

    protected function __construct(array $tables)
    {
        $this->setTables($tables);
        $this->limit = array(new Parameter(0), new Parameter(0));
    }

    public function setTables(array $tables)
    {
        if (count($tables) == 0)
            throw new InvalidArgumentException('Не указано ни одной таблицы');

        $this->from = array();
        foreach ($tables as $table) {
            if (is_string($table)) {
                $this->from[] = new QBTable($table);
            } elseif ($table instanceof QBTable) {
                $this->from[] = $table;
            } else {
                throw new LogicException("В качестве таблицы передан неправильный тип поля");
            }
        }

        $this->reset();
    }

    public function setWhere($conditions)
    {
        if ($conditions === null)
            $this->conditions = null;
        elseif (is_object($conditions))
            $this->conditions = clone $conditions;
        else 
            throw new InvalidArgumentException('Условия where не являются допустимым объектом');

        $this->reset();
    }
    public function setHaving ($conditions)
    {
        $this->havings=$conditions;
        $this->reset();
    }
    public function setOrderby (array $orderlist, array $orderdirectionlist = null)
    {
        foreach ($orderlist as $field)
            if (!($field instanceof Field)) throw new InvalidArgumentException('Допускается только массив объектов типа Field');
        $this->orderby=$orderlist;
        if ($orderdirectionlist)
            $this->orderdirection=$orderdirectionlist;
        else 
            $this->orderdirection = array();
        $this->reset();
    }
    public function setLimit($limit, $offset=0)
    {
        $this->limit = array(new Parameter(intval($limit)), new Parameter(intval($offset)));
        $this->reset();
    }


    public function showTables()
    {
        $res = array();
        foreach ($this->from as $table) {
            $res[] = $table->getTable();
        }

        return $res;
    }

    public function showConditions() {
        return $this->conditions;
    }

    protected function getFrom(&$parameters)
    {
        $sql = " FROM ";
        $froms = array();
        for ($i = 0; $i < count($this->from); $i++) {
            $froms[] = $this->from[$i]." as t".$i;
        }
        $sql .= implode(", ", $froms);

        return $sql;
    }

    protected function getWhere(&$parameters)
    {
        if (null === $this->conditions)
            return "";

        return " WHERE ".$this->conditions->getSql($parameters);
    }

    protected function getHaving(&$parameters)
    {
        if (!$this->havings)
        return "";
        $sql = " HAVING ";
        $sql .= $this->havings->getSql($parameters);
        return $sql;
    }
    protected function getOrderby(&$parameters)
    {
        if (!$this->orderby || !is_array($this->orderby))
        return "";
        $sql = " ORDER BY ";
        foreach ($this->orderby as $i=>$orderby) {
            $direction="";
            if (array_key_exists($i, $this->orderdirection) && $this->orderdirection[$i])
                    $direction = " DESC";
            $sqls[] = $orderby->getSql($parameters).$direction;
        }
        $sql .= implode(", ", $sqls);
        return $sql;
    }
    protected function getLimit(&$parameters)
    {
        if ($this->limit[0]->getParameters() <= 0)
            return "";
        return " LIMIT ".$this->limit[1]->getSql($parameters).", ".$this->limit[0]->getSql($parameters)." ";
    }

    protected function reset()
    {
        $this->paramaters = array();
        $this->sql = false;
    }

    public function sql()
    {
        if (!$this->sql) {
            $this->parameters = array();
            $this->sql = $this->getSql($this->parameters);
        }
        return $this->sql;
    }

    public function parameters()
    {
        return $this->parameters;
    }

    public function countall()
    {
        $this->paramlist = array();
        if ($this->conditions)
            $this->conditions->countall($this->paramlist);

        return $this->paramlist;
    }
}
