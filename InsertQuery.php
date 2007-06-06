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

class InsertQuery extends BasicQuery
{
    private $values;
    private $on_duplicate_update = false;

    public function __construct($tables, $on_duplicate_update = false)
    {
        parent::__construct($tables);

        if (count($this->from) != 1)
            throw new InvalidArgumentException('INSERT can be used only on the single table');

        $this->on_duplicate_update = $on_duplicate_update;
    }

    private function __set($key, $value)
    {
        $this->values[$key] = new Parameter($value);
        $this->reset();
    }

    protected function getSql(&$parameters)
    {
        $sql = $this->getInsert($parameters);
        $sql .= $this->getValues($parameters);

        if (true === $this->on_duplicate_update) {
            $sql .= $this->getUpdate($parameters);
        }

        return $sql;
    }

    public function setValues(array $values)
    {
        $this->values = array();
        foreach ($values as $key => $value) {
            $this->values[$key] = new Parameter($value);
        }
        $this->reset();

        return true;
    }

    private function getInsert(&$parameters)
    {
        $inserts = array();
        foreach (array_keys($this->values) as $key) {
            $inserts[] = '`'.$key.'`';
        }

        $sql = "INSERT INTO ".$this->from[0]->__toString()." (".implode(", ", $inserts).")";

        return $sql;
    }

    private function getValues(&$parameters)
    {
        $values = array();
        foreach ($this->values as $k => $v) {
            $values[] = $v->getSql($parameters);
        }
        $sql = " VALUES (".implode(", ", $values).")";

        return $sql;
    }

    private function getUpdate(&$parameters)
    {
        if (!isset($this->values['id']))
            throw new LogicException("id field is required for ON DUPLICATE KEY UPDATE functionality");

        $values = array();
        foreach ($this->values as $k => $v) {
            if ('id' == $k) // skipping (FIXMIE: не всегда первичным ключом является id)
                continue;

            $values[] = '`'.$k.'` = '.$v->getSql($parameters);
        }

        $sql = " ON DUPLICATE KEY UPDATE ".implode(", ", $values);

        return $sql;
    }
}
