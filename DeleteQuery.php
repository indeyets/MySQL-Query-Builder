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

class DeleteQuery extends BasicQuery
{
    public function __construct(array $tables)
    {
        parent::__construct($tables);
    }

    protected function getSql(&$parameters)
    {
        $sql  = $this->getDelete($parameters);
        $sql .= $this->getUsing($parameters);
        $sql .= $this->getWhere($parameters);
        $sql .= $this->getHaving($parameters);
        $sql .= $this->getOrderby($parameters);
        $sql .= $this->getLimit($parameters);

        return $sql;
    }

    private function getDelete(&$parameters)
    {
        $sql = "DELETE FROM t0";
        return $sql;
    }

    protected function getUsing(&$parameters)
    {
        $sql = " USING ";
        $froms = array();
        for ($i = 0; $i < count($this->from); $i++) {
            $froms[] = $this->from[$i]." as t".$i;
        }
        $sql .= implode(", ",$froms);
        return $sql;
    }

}
