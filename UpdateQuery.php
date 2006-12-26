<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

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
