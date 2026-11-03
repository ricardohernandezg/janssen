<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;
use Janssen\Helpers\Database\Adaptor;

class Parameter implements \Countable
{

    private $members = [];
    private $original_map = null;

    private $empty_treatment = false;
    private $process_value = true;

    private static $normalizers = [
        ['<', '&lt;'],
        ['>', '&gt;'],
    ];

    public function __construct(array $init_vals = [])
    {
        foreach($init_vals as $k=>$v){
            $this->members[$k] = $v;
        }
    }

    /**
     * Returns how many members has the parameter object
     *
     * @return integer
     */
    public function count() : Int
    {
        return count($this->members);
    }

    /**
     * Returns all the members of parameter
     *
     * @return array
     */
    public function getAll()
    {
        return $this->members;
    }

    /**
     * Returns a member of parameter
     *
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public function getMember($name, $default = '')
    {
        return isset($this->members[$name])?$this->members[$name]:$default;
    }

    /**
     * Allow to call parameter as array
     *
     * @param string $name
     * @return void
     */
    public function __get(String $name)
    {
        return $this->getMember($name, null);
    }

    public function getQuotedOrNull($name)
    {
        $member = $this->getMember($name, '');

        if($member == '' && $this->empty_treatment == 'quote') return "''";

        if(is_bool($member))
            $r = "'" . intval($member) . "'";
        elseif(empty($member) && $member !== '0')
            $r  = ($this->empty_treatment == 'null') ? 'null' : $member;
        else{
            $v = $this->members[$name];
            if(is_numeric($v) || is_bool($v))
                $mc = ($this->process_value) ? $this->processNumericValue($v) : $v;    
            elseif (is_string($v))
                $mc = ($this->process_value) ? $this->processTextValue($v) : $v;    
            else
                $mc = $v;
            $r = "'$mc'";
        }
        return $r;
    }

    /**
     * Returns a value that can be put in a query. Checks
     * if value is quotable and returns the value with its quotes
     * or not, or null.
     *
     * @return string
     */
    public function getQueryableOrNull($name)
    {
        $v = $this->getQuotedOrNull($name);
        if(is_numeric($v))
            return $this->members[$name];
        else
            return $v;
    }

    /**
     * Sets or replaces a member value in the parameter list
     *
     * @param string $name
     * @param any $value
     * @return object
     */
    public function setMember($name, $value)
    {
        $this->members[$name] = $value;
        return $this;
    }

    /**
     * Clears all parameters in object
     *
     * @return void
     */
    public function clear()
    {
        $this->members = [];
        return $this;
    }

    /**
     * Allow set parameter members as attribute style
     *
     * @param string $name
     * @param any $value
     */
    public function __set(String $name, $value)
    {
        return $this->setMember($name, $value);
    }

    /**
     * Sets the mapping to be used with this object
     *
     * @param Mapper $map
     * @return object
     */
    public function setMapping(Mapper $map)
    {
        $this->original_map = $map;
        return $this;
    }

    /**
     * Alias of getMapping
     *
     * @return Object
     */
    public function getMap()
    {
        return $this->getMapping();
    }
    
    /**
     * Returns the mapping used for the parameters based on rule
     *
     * @return Object
     */    
    public function getMapping()
    {
        return $this->original_map;
    }

    
    public function getOriginalName($name)
    {
        return $this->original_map->asOutput($name);
    }
    

    /**
     * Returns the parameters as string delimited with $delimiter
     * $fields must be a comma separated list of the paramters to get.
     * 
     */
    public function stringify(string $fields = '', string $delimiter = ',')
    {
        $ar_f = explode(",", $fields);

        if(is_array($ar_f) && count($ar_f) == 1 && $ar_f[0] == '')
            $ar_f = false;

        return $this->stringifyFromArray(($ar_f?$ar_f:[]), $delimiter);
    }

    public function stringifyFromArray(array $fields, $delimiter = ',')
    {
        $ret = '';
        if(empty($fields))
            $fields = array_keys($this->members);
        
        foreach($fields as $field){
            $field = trim($field);
            $ret .= $this->getQueryableOrNull($field) . $delimiter;
        }
        // remove last delimiter
        $ret = substr($ret, 0, strlen($ret)-strlen($delimiter));
        return $ret;
    }

    public function updatefyFromArray(array $fields, $delimiter = ',', $ignore_absents = false)
    {
        $ret = '';
        if(empty($fields))
            $fields = array_keys($this->members);
        
        foreach($fields as $name){
            $name = trim($name);
            $value = $this->getMember($name, '');
            if(empty($value) && $value !== 0 && $ignore_absents)
                continue;

            $ret .= " $name  = " . $this->getQueryableOrNull($name) . $delimiter;
        }
        // remove last delimiter
        $ret = substr($ret, 0, strlen($ret)-strlen($delimiter));
        return $ret;
    }

    public function updatefy(string $fields = '', $delimiter = ',', $ignore_absents = false)
    {
        $ar_f = explode(",", $fields);

        if(is_array($ar_f) && count($ar_f) == 1 && $ar_f[0] == '')
            $ar_f = false;

        return $this->updatefyFromArray(($ar_f?$ar_f:[]), $delimiter, $ignore_absents);
    }

    /**
     * @param string $fields
     * @param Adaptor $adaptor
     * @return string|false
     */
    public function getBindables(string $fields, Adaptor $adaptor)
    {
        $ar_f = explode(",", $fields);

        if(is_array($ar_f) && count($ar_f) == 1 && $ar_f[0] == '')
            return false;

        $ret = ['bindings' => [], 'types' => []];
        // iterate the array and return the value and its correct binding type
        foreach ($ar_f as $v){
            $type = $adaptor::determineType($v);
        }

        return $ret;
    }

    /**
     * Make empty or blank fields as null
     * 
     * @return object
     */
    public function TreatBlanksAsNull()
    {
        $this->empty_treatment = 'null';
        return $this;
    }

    /**
     * Make empty or blank quoted empties
     */
    public function respectEmpties()
    {
        $this->empty_treatment = 'quote';
        return $this;
    }


    /**
     * Process value to substitute values before send to string
     *
     * @param string $value
     * @return string
     */
    private function processTextValue($value)
    {

        $p = $value;
        foreach(self::$normalizers as $v){
            $i = strpos($p, $v[0]);
            if($i !== false)
                $p = str_replace($v[0], $v[1], $p);
        }
        
        $p = str_replace("\\'", "'", $p);
        $i = strpos($p, "'");
        if($i !== false)
            $p = str_replace("'", "\\'", $p);

        return $p;
    }

    /**
     * @todo check whats this for
     */
    private function processNumericValue($value)
    {
        return $value;
    }

    /**
     * Replaces single quote in string
     *
     * @param string $value
     * @return string
     */
    private function replaceSingleQuote($value)
    {
        // first we delete all \' quotes to replace
        $vc = str_replace("\\'", "'", $value);
        $i = strpos($vc, "'");
        if($i !== false)
            $vc = str_replace("'", "\\'", $vc);

        return $vc;
        
    }

    public static function normalize($value)
    {
        $n = '';
        $lf = self::$normalizers;
        $lf[] = ["\\'", "'"];
        foreach ($lf as $v)
        {
            $i = strpos($value, $v[1]);
            if($i !== false)
                $n = str_replace($v[1], $v[0], $value);
        }

        return $n;
    }

    /**
     * To use in case of Dynamic Js generation and need of normalizers
     * Returns the Parameter normalizers as JS array to be used to normalize
     * in script
     *
     * @return string
     */
    public static function jsNormalizers()
    {
        $n = '[';
        foreach(self::$normalizers as $v)
        {
            $n .= "[{$v[0]},{$v[1]}],";
        }
        $n .= ']';
    }
}