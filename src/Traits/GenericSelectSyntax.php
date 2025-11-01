<?php 

namespace Janssen\Traits;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait GenericSelectSyntax
{
    use InstanceGetter;
    
    protected static $_select = [];

    private static function isValidFieldName($name)
    {
        $er = '/^[A-Za-z\$\#\_]{1}[A-Za-z\$\#\_0-9]*/';
        return preg_match($er, $name) == 1;
    }

    public static function cleanSelect()
    {
        self::$_select = [];
        return self::me();
    }
}