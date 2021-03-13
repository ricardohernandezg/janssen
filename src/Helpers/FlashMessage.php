<?php 

namespace Janssen\Helpers;

use Janssen\Engine\Session;

class FlashMessage
{

    private static $collection = [];
    private static $sess_var_name = '_janssen_flash';

    public static function addMessage(String $key, String $message, String $type = 'info')
    {
        self::$collection[$key] = ['message' => $message, 'type' => $type];
        return self::$collection[$key];
    }

    public static function getMessages($key = null)
    {
        if(is_null($key)){
            $ret = self::$collection;
            self::clear();
            return $ret;
        }else{
            if(isset(self::$collection[$key])){
                $ret = self::$collection[$key];
                unset (self::$collection[$key]);
                self::forceUpdate();
                return $ret;
            }else
                return false;
        }
            
    }

    public static function has($key)
    {
        return array_key_exists($key, self::$collection);
    }

    public static function howMany()
    {
        return count(self::$collection);
    }

    public static function clear()
    {
        Session::removeField(self::$sess_var_name);
        self::$collection = [];
    }

    public static function getSessionVarName()
    {
        return self::$sess_var_name;
    }

    public static function forceUpdate($delete = false)
    {
        Session::setValue(self::$sess_var_name, ($delete)?self::getMessages():self::$collection);
    }

    public static function bulkLoadFromSession()
    {
        $flashed = Session::getValue(self::$sess_var_name);
        if(!is_null($flashed) && is_array($flashed)){
            foreach($flashed as $k=>$f)
            {
                self::addMessage($k, $f['message'], $f['type']);
            }
        }
    }

    
}