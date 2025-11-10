<?php 

namespace Janssen\Traits\GenericSQLSyntax;

trait GenericFieldSyntax
{

    /**
     * Checks if a text is a valid SQL field name
     * 
     * Based on ANSI-SQL-92
     * 
     */
    public static function isValidFieldName(string $name) : string
    {
        $er = '/^[A-Za-z][A-Za-z0-9_]{0,127}$/im';
        return preg_match($er, $name) == 1;
    }
}