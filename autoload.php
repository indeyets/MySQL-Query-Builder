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

function mqb_autoload($class_name)
{
    static $prefix = null;
    static $classes = null;

    if (null === $prefix) {
        $prefix = dirname(__FILE__).DIRECTORY_SEPARATOR;
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
            'AllFields' => 'additional.php',
            'Field' => 'additional.php',
            'sqlFunction' => 'additional.php',
            'Aggregate' => 'additional.php',
            'Parameter' => 'additional.php'
        );
    }

    if (isset($classes[$class_name]))
        require $prefix.$classes[$class_name];
}

spl_autoload_register('mqb_autoload');