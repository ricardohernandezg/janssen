<?php 

namespace Janssen\Helpers;

use Janssen\Engine\Request;
use Janssen\Engine\Config;

class Auth 
{

    private static $guard_reasons = [];

    /**
     * Ask all guards in the request to authorize an action
     * If only one allows it, its good
     *
     * @param Request $request
     * @return Bool
     */
    public static function authorize(Request $request)
    {
        $ret = false;
        $guards = $request->getIntendedGuards();
        foreach($guards as $guard){
            if ($guard->authorize($request) === true){
                $request->authorizedBy(get_class_name($guard));
                return true;
            }
        }
        return false;
    }

    /**
     * Ask all guards in the request to authenticate a user
     * with the given credentials. If only one is positive, it's good
     *
     * @param Request $request
     * @return Bool
     */
    public static function authenticate(Request $request)
    {
        $ret = false;
        $guards = $request->getIntendedGuards();
        foreach($guards as $guard){
            if ($guard->authenticate($request) === true)
                return true;
            else{
                $reason = $guard->why();
                if(is_array($reason)){
                    foreach($reason as $r){
                        self::addReason(get_class_name($guard), $r);
                    }
                }
            }                
        }
        return false;
    }

    /**
     * Revokes all grants given by all Guards
     *
     * @return void
     */
    public static function revokeAll()
    {
        Guard::revokeAll();
        return true;
    }

    private static function addReason($guard, $reason)
    {
        self::$guard_reasons[] = ['guard' => $guard, 
            'field' => $reason['field'], 
            'reason' => $reason['reason'],
            'type' => $reason['type']
        ];
    }

    public static function why()
    {
        return self::$guard_reasons;
    }

    public static function guard($guard)
    {
        $guard_class = '\App\Auth\\' . transform_to_class_name($guard) . 
            ((substr($guard, -5, 5) !== 'Guard') ? 'Guard' :'');

        if(class_exists($guard_class))
            return new $guard_class;
        else
            throw new Exception('Guard class doesn\'t exist', 500);
    }

    private static function getConfigAuthSettings($guard, $submember = "")
    {
        $g = (string) $guard;
        $g = strtolower($g);
        if(substr($g, -5, 5) == 'guard')
            $g = substr($g,0, strlen($g)-5);

        $s = "auth.$g" . (($submember) ? ".$submember" : '');

        return Config::get($s, false);
    }


    public static function who(Request $request)
    {
        $res = [];
        $wa = self::guard($request->whoAuthorizes());
        $c = self::getConfigAuthSettings($wa, 'who');
        
        if(!$c) return [];
        
        $g = Guard::resolve($wa);
        $data = $g->getData();
        foreach($c as $v){
            $res[$v] = $data[$v] ?? null;
        }
        return $res;
    }
}