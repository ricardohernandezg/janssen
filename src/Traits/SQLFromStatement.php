<?php 

namespace Janssen\Traits;

trait SQLFromStatement
{

    use \Janssen\Traits\InstanceGetter;
    use \Janssen\Traits\StaticCall;

    /**
     * From part of statement
     */

    private static $table = "";

    public static function from(String $table_name)
    {
        self::$table = $table_name;
        return self::me();
    }

}