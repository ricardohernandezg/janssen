<?php 

namespace Janssen\Traits;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait GenericSelectSyntax
{
    use InstanceGetter;
    
    protected static $_select = [];

    protected function prepareSelect(Array $parted_sql)
    {
        $parted_sql['select'] = $this->flatFields();
        return $this->flatSQL($parted_sql);
    }

    private function flatFields()
    {
        if(!empty(self::$_select))
            return implode(', ', self::$_select);
        else
            return '*';
    }

    private static function isValidFieldName($name)
    {
        $er = '/^[A-Za-z\$\#\_]{1}[A-Za-z\$\#\_0-9]*/';
        return preg_match($er, $name) == 1;
    }

}