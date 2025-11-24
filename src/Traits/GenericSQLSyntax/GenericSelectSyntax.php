<?php 

namespace Janssen\Traits\GenericSQLSyntax;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait GenericSelectSyntax
{
    use InstanceGetter;
    
    private static $__fields = [];

    protected function prepareSelect(array $parted_sql, array $mapping = [])
    {
        self::$__fields = []; 
        foreach($parted_sql['select'] as $k=>$field_name){
            self::$__fields[] = $field_name . (array_key_exists($field_name, $mapping) ? " AS " . $mapping[$field_name]:'');
        }
        return "SELECT " . ($parted_sql['distinct']==true?' DISTINCT ':'') . $this->flatSelect($parted_sql);
    }

    private function flatSelect()
    {
        if(!empty(self::$__fields))
            return implode(', ', self::$__fields);
        else
            return '*';
    }

}