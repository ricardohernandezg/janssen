<?php 

namespace Janssen\Engine;

use Janssen\Helpers\Exception;
use Janssen\Engine\Request;

class Mapper
{

    use \Janssen\Traits\InstanceGetter;

    private $map = [];

    public function __construct($map = 'auto')
    {
        // if user try to make and automapping, we set the array
        // empty. That will be the signal of auto
        if(!is_array($map) && $map == 'auto'){
            $map = [];
        }

        if($map instanceof Ruleset)
            $map = $map->getMapping();

        $this->setMap($map);
    }

    /**
     * Sets a map
     */
    public function setMap(Array $map)
    {
        $this->map = $map;
        return $this;
    }
    
    /**
     * Checks if a input member exists
     */
    public function has($key)
    {
        return array_key_exists($key, self::$map);
    }

    /**
     * Returns the map
     *
     * @return Array
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * Returns true if Mapper is working in auto mode
     *
     * @return boolean
     */
    public function isAuto()
    {
        return empty($this->map);
    }

    /**
     * Returs the map name to be used inside. If user
     * sends parameter empty we send the full map as defined
     * in the Rule in the form external=>internal
     *
     * @param String|Array $member
     * @return String|Array
     */
    public function asInput($member = null)
    {
        // if empty we return all the input members     
        if(empty($member))
            return array_flip($this->map);
        else{
            if(is_array($member)){
                $r = [];
                foreach($member as $v){
                    $r[$member] = $this->asInput($member);
                }
                $ret = $r;
            }elseif(is_string($member)){
                $f = array_search($member, $this->map);
                $ret = ($f === false)?$member:$f;
            }else
                $ret = false;

        }
        return $ret;        
    }

    /**
     * Returs the map name to be used outside. If user
     * sends parameter empty we send the full map as defined
     * in the Rule in the form internal=>external
     *
     * @param String|Array $members
     * @return String|Array
     */
    public function asOutput($member = null)
    {

        // if empty we return all the output members     
        if(empty($member))
            return $this->map;
        else{
            if(is_array($member)){
                $r = [];
                foreach($member as $v){
                    $r[$member] = $this->asOutput($member);
                }
                $ret = $r;
            }elseif(is_string($member)){
                if(array_key_exists($member, $this->map)){
                    $ret = $this->map[$member];
                }else
                    $ret = $member;
            }else
                $ret = false;
            
        }
        return $ret;
    }
    
    /**
     * Create field as alias string for use in Select statement.
     * Array must be [$field] => alias style to be processed. Intended to be
     * used with mapOutput return
     *
     * @param Array $map
     * @return void
     */
    public static function queryfyMapForOutput(Array $map)
    {
        $s = '';
        foreach($map as $k=>$v)
        {
            $s .= "$k as $v, ";
        }
        $s = substr($s, 0, strlen($s)-2);
        return $s;
    }

    /**
     * Get output name for a member
     *
     * @param Ruleset $rule
     * @param String $input_member_name
     * @return String
     */
    public static function getOutputMember(Ruleset $rule, $input_member_name)
    {
        $oi = self::getOwnInstance()->setMap($rule->getMapping());
        return $oi->asOutput($input_member_name);
    }

    /**
     * Get the input name for a member
     *
     * @param Ruleset $rule
     * @param String $output_member_name
     * @return String
     */
    public static function getInputMember(Ruleset $rule, $output_member_name)
    {
        $oi = self::getOwnInstance()->setMap($rule->getMapping());
        return $oi->asInput($output_member_name);
    }
}