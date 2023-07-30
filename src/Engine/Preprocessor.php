<?php 

namespace Janssen\Engine;

use Janssen\Helpers\Exception;
use Janssen\Engine\Request;

abstract class Preprocessor
{
    public $except = [];

    public abstract function handle(Request $request);

    public function handleError(){
        throw new Exception('Invalid request! Thrown by ' . get_class_name($this), 400);
    }

    public static function processHandlers($handlers, $request)
    {
        // call all the user registered handlers, if return
        // true we call the next, if not, we return the response
        // of last unsuccessful handler
        foreach($handlers as $handler)
        {
            if(is_array($handler)){
                $htl = $handler[0];
                $m = $handler[1];

                if(is_array($m)){
                    $m = array_map("\strtoupper", $m);
                    $mc = in_array($request->method(), $m);
                }else    
                    $mc = ($request->method() == trim(strtoupper($m)));

                if(!$mc)
                    continue;

            }else
                $htl = $handler;

            $h = new $htl;
            if($h instanceof Preprocessor){

                //@todo here we need to check access method and look for exceptions
                //$rp = (is_array($h->except) && !empty($h->except) && self::isThisRequestExcepted($request, $h->except));
                /*
                if($rp)
                    return true;*/

                $r = $h->handle($request);
                if($r instanceof Response) // response returns control directly to app
                    return $r;
                elseif ($r !== true)
                    return $h->handleError();
                    //throw new Exception('Invalid request! Thrown by ' . get_class_name($h), 400);    
            }else
                throw new Exception('Handler must be instance of Preprocessor', 500);
            
        }
        return true;
    }

    protected static function isThisRequestExcepted(Request $request, Array $except = [])
    {
        $m = $request->method();
        $p = ($m == 'GET') ? $request->getPath() : $request->getUserActionAsPath();
        $found = in_array($p, $except);

        /*
        foreach($except as $v)
        {
            if(is_array($v)){
                
                $found = ($v[0] == $p && $m == strtoupper($v[1]));
            }else{
                $found = ($v == $p);
            }

            if($found)
                return true;

        }
        */
        return $found;
    }
}