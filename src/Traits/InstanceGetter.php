<?php 
/**
* Adds functionality to allow a static method to call concrete methods
* 
*
* @package  Janssen\Traits
*/

namespace Janssen\Traits;

trait InstanceGetter
{

    private static $me = false;

    /**
     * Returns concrete instance of own class from static call
     *
     * @return Object
     */
    protected static function getOwnInstance()
    {
        $cc = get_called_class();
        $class = new $cc;
        return $class;
    }

    /**
     * @return Object
     */
    private static function me()
    {
        if(!is_object(self::$me))
            self::$me = new static();

        return self::$me;
    }
    
    private static function notMe()
    {
        self::$me = false;
    }

}