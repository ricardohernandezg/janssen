<?php 

namespace Janssen\Traits\GenericSQLSyntax;

trait GenericFieldSyntax
{

    private static $field_regex = '[A-Za-z][A-Za-z0-9_\(\)]{0,127}';

    /**
     * Checks if a text is a valid SQL field name
     * 
     * Based on ANSI-SQL-92
     * 
     */
    public static function isValidFieldName(string $name) : string
    {
        $re = "/^" . self::$field_regex . "$/im";
        return preg_match($re, $name) == 1;
    }

    /**
     * 
     */
    public static function createMappedString(string $sql, array $mapping) : string
    {
        $ret = "";
        // get all the fields from the select part
        $re1 = "/SELECT\s+(.*?)\s+FROM/im";
        $m1 = $m2 = [];
        $i = preg_match($re1, $sql, $m1);
        if($i){
            // extract each field from the select part
            $re2 = "/" . self::$field_regex . "/im";

        }

        return $ret;
    }
}