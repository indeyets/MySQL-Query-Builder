<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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

        return "SELECT t0.*";
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
