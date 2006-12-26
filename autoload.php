<?php

function mqb_autoload($class_name)
{
    static $prefix = null;
    static $classes = null;

    if (null === $prefix) {
        $prefix = dirname(__FILE__);
    }

    if (null === $classes) {
        $classes = array(
            'BasicQuery' => 'BasicQuery.php',
            'DeleteQuery' => 'DeleteQuery.php',
            'InsertQuery' => 'InsertQuery.php',
            'SelectQuery' => 'SelectQuery.php',
            'UpdateQuery' => 'UpdateQuery.php',
            'QBTable' => 'additional.php',
            'Operator' => 'additional.php',
            'NotOp' => 'additional.php',
            'AndOp' => 'additional.php',
            'OrOp' => 'additional.php',
            'XorOp' => 'additional.php',
            'Condition' => 'additional.php',
            'Field' => 'additional.php',
            'sqlFunction' => 'additional.php',
            'Aggregate' => 'additional.php',
            'Parameter' => 'additional.php'
        );
    }

    if (isset($classes[$class_name]))
        require $prefix.DIRECTORY_SEPARATOR.$classes[$class_name];
}

spl_autoload_register('mqb_autoload');