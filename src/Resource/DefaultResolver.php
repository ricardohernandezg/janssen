<?php 

namespace Janssen\Resource;

class DefaultResolver
{
    
    private static $aliased_defaults = [
        'mysql' => '\Janssen\Helpers\Database\Adaptors\MySqlAdaptor',
        'pgsql' => '\Janssen\Helpers\Database\Adaptors\PostgresAdaptor',
        'ViewResponse' => '\Janssen\Helpers\Response\ViewResponse',
        'JsonResponse' => '\Janssen\Helpers\Response\JsonResponse',
        ];

    
    public static function resolve($alias)
    {
        return (empty(self::$aliased_defaults[$alias])? false : new self::$aliased_defaults[$alias]);
    }

    /**
     * Add external aliases to be used by internal functions that uses the alias resolver
     *
     * @param Array $external_aliases
     * @return Bool
     */
    public static function loadExternal($external_aliases){
        if(!empty($external_aliases) && is_array($external_aliases))
        {
            self::$aliased_defaults += $external_aliases;
            return true;
        }else
            return false;

    }
}