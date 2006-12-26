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

class UpdateQuery extends BasicQuery
{
    private $sets = null;

    public function __construct(array $tables)
    {
        parent::__construct($tables);
    }

    protected function getSql(&$parameters)
    {
        return $this->getUpdate($parameters).
            $this->getSet($parameters).
            $this->getWhere($parameters).
            $this->getHaving($parameters).
            $this->getOrderby($parameters).
            $this->getLimit($parameters);
    }

    private function getUpdate(&$parameters)
    {
        return "UPDATE ".$this->from[0]." t0";
    }

    public function setValues(array $sets)
    {
        if (count($sets) == 0)
            return;

        $this->sets = array();
        foreach ($sets as $set => $value) {
            $this->sets[$set] = new Parameter($value);
        }
        $this->reset();
    }

    protected function getSet(&$parameters)
    {
        if (null === $this->sets)
            return "";

        $sqls = array();
        foreach ($this->sets as $set => $value) {
           $sqls[] = '`'.$set.'`='.$value->getSql($parameters);
        }

        return " SET ".implode(", ", $sqls);
    }
}
