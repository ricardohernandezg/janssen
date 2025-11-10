<?php 

namespace Janssen\Traits\GenericSQLSyntax;

use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;

trait GenericLimitSyntax
{

    use InstanceGetter;

    protected static $limit = -1;
    protected static $offset = -1;

    protected function prepareLimitOffset()
    {
        // sql doesn't allow use only offset
        $lo = '';
        if(self::$limit > -1 && self::$offset > -1)
            $lo = self::$limit . ' OFFSET ' . self::$offset; 
        elseif (self::$limit > -1)
            $lo = self::$limit; 
        
        return $lo;
    }

}