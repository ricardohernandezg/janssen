<?php 

namespace Janssen\Engine;

use Janssen\Helpers\Exception;

abstract class Event
{

    private static $callables = [
        'app.afterinit' => [],
        'viewresponse.beforerender' => [],
        'jsonresponse.beforerender' => [],
        'rawresponse.beforerender' => []
    ];

    private static function exists(string $event)
    {
        return array_key_exists(strtolower($event), self::$callables);
    }
    
    public static function listen(string $event, callable $callable)
    {
        
        if(!self::exists($event)){
            throw new Exception("This event is not available");
        }

        self::$callables[$event][] = $callable;

    }

    /**
     * Each event must have this function that will be called
     *
     * @param Array $args
     * @return void
     */
    abstract function handle($args);

    /**
     * Ask config for all events and run the correspondent if found
     *
     * @todo add support for several handles to the same event
     * @todo look for a way to cancel all using the handler response
     * 
     * @param String $event_name
     * @return void
     */
    public static function invoke($event, $invoker, ...$args)
    {
        $event = strtolower($event);

        if(!self::exists($event)){
            throw new Exception("This event is not available");
        }        

        foreach(self::$callables[$event] as $k=>$callable){
            $callable($invoker, $args);
        }

        return null;
    }

    /**
     * create a new event to be listened
     */
    public static function create($event_name)
    {
        $event_name = strtolower($event_name);
        self::$callables[$event_name] = [];
    }
}