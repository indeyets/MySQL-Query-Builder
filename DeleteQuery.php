<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
