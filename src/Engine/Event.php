<?php 

namespace Janssen\Engine;

use Janssen\Helpers\Exception;

abstract class Event
{

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
    public static function invoke($event_name, ...$args)
    {
        /*
        $events = Config::getEvents();
        if(empty($events))
            return $args;*/

        $a = explode('.',$event_name, 2); // name shouldn't have more than 2 members class.event
        if(is_array($a) && !empty($a[0]) && !empty($a[1]))
            $event_path = $a[0] . "\\" . $a[1] . 'Event';
        else
            throw new Exception("Invalid Event name to invoke");

        $class = "App\\Event\\" . $event_path;
        if(class_exists($class, false)){
            $i = new $class;
            if(method_exists($i, 'handle'))
                return call_user_func_array([$i, "handle"], $args);
            else
                throw new Exception("Class Event neeed the handle() event to be defined");   
        }
        
        return $args;
    }

}