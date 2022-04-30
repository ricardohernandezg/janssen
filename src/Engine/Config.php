<?php 

namespace Janssen\Engine;

use Exception;
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
        if(isset($_ENV[$key]))
            return getenv($key);
        else
            return $default;
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
        $path = trim($path);
        if(substr($path,-1,1) !== '/')
            $path .= '/';

        if(file_exists("$path.env")){
            try{
                $dotenv = \Dotenv\Dotenv::create($path);
                $dotenv->load();
            }catch(Exception $e){
                throw new Exception('You need to use Dotenv if you want to load .env files!');
            }
        }
    }

    /**
     * Get a configuration setting
     *
     * @param String $name
     * @param String $default
     * @return String|Array
     */
    public static function get($name = null, $default = null)
    {
        if(empty($name))
            return self::$settings;

        $ud = self::undot($name);
        if(count($ud) > 1){
            $s = self::$settings;
            foreach($ud as $v){
                $s =  $s[$v] ?? $default;
            }
            return $s;
        }else
            return self::$settings[$name] ?? $default;

        //return isset(self::$settings[$name])?self::$settings[$name]:$default;
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
        $ud = self::undot($name);

        if(count($ud) > 1){
            $s = [];
            $p = &$s;
            
            for($i = 0; $i < count($ud); $i++){
                if($i == 0){
                    $s[$ud[$i]] = [];
                    $p = &$s[$ud[$i]];
                    continue;
                }
                $p[$ud[$i]] = [];
                $p = &$p[$ud[$i]];

            }
            $p = $value;
            self::$settings = $s + self::$settings;
        }else
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

    private static function undot($text)
    {
        return explode('.', $text);
    }
}