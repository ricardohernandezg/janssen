<?php 

namespace Janssen\Engine;

class Config
{
    private static $settings = [];

    /**
     * Get a configuration setting
     *
     * @param String $name
     * @param String $default
     * @return String
     */
    public static function get($name = null, $default = null)
    {
        if(empty($name))
            return self::$settings;

        return empty(self::$settings[$name])?$default:self::$settings[$name];
    }

    /**
     * Sets a configuration value
     *
     * @param String $name
     * @param Any $value
     * @return void
     */
    public static function set($name, $value)
    {
        self::$settings[$name] = $value;
    }

    /**
     * Rewrites the settings internal values. 
     * This function is intended only for internal use
     *
     * @param Array $settings
     * @return void
     */
    public static function setAll($settings)
    {
        self::$settings = $settings;
    }

    /**
     * Special function that will retrieve only the events part of the config
     *
     * @return Array|Boolean
     */
    public static function getEvents()
    {
        return isset(self::$settings['events'])?self::$settings['events']:false;   
    }
}