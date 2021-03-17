<?php 

namespace Janssen\Engine;

class Config
{
    private static $settings = [];

    /**
     * Tries to load a value from env and return it. In case
     * value is inexistent, return $default
     * 
     * This function differs from get() as this goes directly through
     * getenv and is built to the configuration array take advantage of
     * env files loaded by DotEnv in case user chose to use it.
     *
     * @param String $key
     * @param Any $default
     * @return Any
     */
    public static function env($key, $default){
        $v = getenv($key);
        if(is_null($v))
            return $default;
        else
            return $v;
    }

    /**
     * 
     * Load variables and put them in PHP's env space
     * 
     * @param String $path
     * @return void
     */
    public static function loadConfigFromEnv($path)
    {
        if(class_exists('\Dotenv\Dotenv', false)){
            $dotenv = \Dotenv\Dotenv::create($path);
            $dotenv->load();
        }
    }


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