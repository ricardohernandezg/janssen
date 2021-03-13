<?php 

namespace Janssen\Resource;

class DefaultResolver
{
    
    private static $aliased_defaults = [
        'mysqli' => '\Janssen\Helpers\Database\Adaptors\MySqlAdaptor',
        'pgsql' => '\Janssen\Helpers\Database\Adaptors\PostgresAdaptor',
        ];

    
    public static function resolve($alias)
    {
        return (empty(self::$aliased_defaults[$alias])?false:self::$aliased_defaults[$alias]);
    }

}