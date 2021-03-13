<?php 

namespace Janssen\Helpers;

//use Janssen\Helpers\Exception;
use Janssen\Engine\Request;
use Janssen\Engine\Session;
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
    public static function revoke(Request $request)
    {
        Session::removeField('guards');
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

    /*
    protected $guard;

    public function __construct($guard = null)
    {
        // guard name is null, use the default
        if(empty($guard))
            $guard = \Janssen\App::getConfig('default_guard');
        if(trim(strtolower($guard)) == 'nobody')
            throw new Exception('Invalid guard configuration', 500);

        $this->guard($guard);
    }
    */
    
    public static function guard($guard)
    {
        // the class must be in \App\Auth
        $guard_class = '\App\Auth\\' . transform_to_class_name($guard) . 'Guard';
        if(class_exists($guard_class))
            return new $guard_class;
        else
            throw new Exception('Guard class doesn\'t exist', 500);
    }
    

}