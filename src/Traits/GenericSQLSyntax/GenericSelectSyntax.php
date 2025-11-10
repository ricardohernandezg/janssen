<?php 

namespace Janssen\Traits\GenericSQLSyntax;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait GenericSelectSyntax
{
    use InstanceGetter;
    
    protected static $select = [];

    protected function prepareSelect(Array $parted_sql)
    {
        $parted_sql['select'] = $this->flatFields();
        return $this->flatSQL($parted_sql);
    }

    private function flatFields()
    {
        if(!empty(self::$select))
            return implode(', ', self::$select);
        else
            return '*';
    }

}