<?php 

namespace Janssen\Helpers;

use Janssen\Engine\Session;
use Janssen\Engine\Request;
use Janssen\Engine\Config;

abstract class Guard
{
    
    /**
     * Save any message/reason related to the authentication/authorization
     * process that could be useful for the invoker to know what happened
     *
     * @var array
     */
    private $reason = [];

    /**
     * Grant variable to store the status in session
     *
     * @var string
     */
    private $grant_var_name = '_granted';

    /**
     * Authenticate the user with the given request data
     * and sets session
     */
    public abstract function authenticate(Request $request);

    /**
     * Checks the session and uthorizes the request for the 
     * given user
     */
    public abstract function authorize(Request $request);

    /**
     * Save the reason as piece of info for the invoker.
     * Guards can optionally save the reason for a reject if they
     * want the invoker to know what happened
     *
     * @param String $field
     * @param String $reason
     * @return Guard
     */
    protected function giveReason($field, $reason, $type = 'error')
    {
        $this->reason[] = ['field' => $field, 'reason' => $reason, 'type' => $type];
        return $this;
    }

    /**
     * Returns the reason/messages the Guard want to pass to invoker
     *
     * @return void
     */
    public function why()
    {
        return $this->reason;
    }

    /**
     * Sets session values to authenticaded
     */
    public function grant($additional_fields = []){        
         
        $grant_data = [];
        $grant_data[$this->grant_var_name] = true;
        foreach($additional_fields as $k=>$v){
            $grant_data[$k] = $v;
        }
        $this->setData($grant_data);
        $granted_by = get_class_name(get_class($this));
        Request::authorizedBy($granted_by);
        return $this;
    }

    /**
     * Set a key/value pair to the guard data
     *
     * @param String|Array $key
     * @param Any $value
     * @return Object
     */
    public function setData($key, $value = null)
    {
        $g = Session::getValue('guards', []);
        $guard_name = get_class_name(get_class($this));
        if(is_array($key)){
            foreach($key as $k=>$v){
                $g[$guard_name][$k] = $v;   
            }
        }else{
            $g[$guard_name][$key] = $value;
        }
        Session::setValue('guards', $g);
        return $this;
    }

    public function revoke()
    {
        $g = Session::getValue('guards', []);
        $granted_by = get_class_name(get_class($this));
        unset($g[$granted_by]);
        if(empty($g))
            Session::removeField('guards');
        else
            Session::setValue('guards', $g);
        
        Request::authorizedBy('');
    }

    /**
     * Checks wether this Guard has granted
     *
     * @return boolean
     */
    public function isGranted()
    {
        $c = get_class_name(get_called_class());
        $md = self::getData($c);
        $granted = false;
        if(is_array($md) && array_key_exists($this->grant_var_name, $md))
            $granted = $md[$this->grant_var_name];

        return $granted;
    }

    /**
     * Returns the name of the guard
     *
     * @return void
     */
    public function getName()
    {
        return get_class();
    }

    /**
     * Resolve the guard class by given name
     *
     * @param String|Array $guard
     * @return Object|Array
     */
    public static function resolve($guard = null)
    {
        //$r = [];
        if(empty($guard))
            $guard = Config::get('default_guard', 'No');

        if(is_array($guard)){
            $r = [];
            foreach($guard as $g){
                $gi = self::makeGuardName($g);
                $r[] = new $gi;
            }
        }else{
            $gi = self::makeGuardName($guard);
            $r = new $gi;
        }
                
        return $r;        
    }
   
    public static function getData($guard = null)
    {
        // if user don't send a guard, try to get it from the request
        if(empty($guard))
            $guard = Request::whoAuthorizes();
            
        //$guard = \engine_config('default_guard');

        $sg = Session::getValue('guards');
        return empty($sg)?false:(array_key_exists($guard, $sg)?$sg[$guard]:false);
    }

    
    private static function makeGuardName($guard)
    {
        $guard = trim($guard);
        $ag = (substr($guard,-5) == 'Guard');
        return '\App\Auth\\' . transform_to_class_name($guard) . (!$ag?'Guard':'');
    }

}
