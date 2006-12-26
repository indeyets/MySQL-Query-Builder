<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

class InsertQuery extends BasicQuery
{
    private $values;
    private $on_duplicate_update = false;

    public function __construct(array $tables, $on_duplicate_update = false)
    {
        if (count($tables) > 1)
            throw new InvalidArgumentException('INSERT проводится только по одной таблице');

        parent::__construct($tables);

        $this->on_duplicate_update = $on_duplicate_update;
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

        $sql = "INSERT INTO ".$this->from[0]." (".implode(", ", $inserts).")";

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
