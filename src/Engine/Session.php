<?php 

namespace Janssen\Engine;

class Session
{

    /**
     * Starts a session
     * 
     * @return void
     */
    public static function start()
    {
        if(!self::is_session_started()) 
            session_start();
    }

    /**
     * Ends a session
     *
     * @return void
     */
    public static function destroy()
    {
        if (self::is_session_started()) 
            session_destroy();
    }

    /**
     * Gets a variable from $_SESSION
     *
     * @param String $name
     * @param String $default
     * @return Array|Bool
     */
    public static function getValue($name, $default = null){
        return (empty($_SESSION[$name])?$default:$_SESSION[$name]);
    }
    
    /**
     * Sets a $_SESSION value
     * 
     * RequestHelper should be refreshed after use
     *
     * @param String $name
     * @param String $value
     * @return void
     */
    public static function setValue($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    /**
     * Returns all the $_SESSION data
     *
     * @return Array
     */
    public static function all()
    {
        return $_SESSION;
    }

    /**
     * Removes a field from Session storage
     *
     * @param String $name
     * @return void
     */
    public static function removeField($name)
    {
        self::setValue($name, NULL);
        unset($_SESSION[$name]);
    }

    /**
     * Checks if there is session started already
     *
     *
     * @see http://php.net/manual/es/function.session-status.php
     * @return Boolean
     */
    public static function is_session_started()
    {
        if (php_sapi_name() !== 'cli') {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                return session_status() === PHP_SESSION_ACTIVE ? true : false;
            } else {
                return session_id() === '' ? false : true;
            }
        }
        return false;
    }

}