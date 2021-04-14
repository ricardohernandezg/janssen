<?php 

namespace Janssen\Engine;

use Janssen\Engine\Mapper;

class Parameter implements \Countable
{

    private $members = [];
    private $original_map = null;

    private $blanks_as_null = false;

    /**
     * Returns how many members has the parameter object
     *
     * @return Integer
     */
    public function count()
    {
        return count($this->members);
    }

    /**
     * Returns all the members of parameter
     *
     * @return Array
     */
    public function getAll()
    {
        return $this->members;
    }

    /**
     * Returns a member of parameter
     *
     * @param String $name
     * @param String $default
     * @return String
     */
    public function getMember($name, $default = '')
    {
        return isset($this->members[$name])?$this->members[$name]:$default;
    }

    public function getQuotedOrNull($name)
    {
        $gm = $this->getMember($name, '');
        if(empty($gm) && $gm != '0')
            if($this->blanks_as_null) 
                $r = 'null';
            else
                $r = $gm;
        else
            $r = "'{$this->members[$name]}'";
        return $r;
    }

    /**
     * Returns a value that can be put in a query. Checks
     * if value is quotable and returns the value with its quotes
     * or not, or null.
     *
     * @return String
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
     * If empty the full object wi
     * 
     */
    public function stringify(String $fields = null, $delimiter = ',')
    {
        $ar_f = explode(",", $fields);

        if(is_array($ar_f) && count($ar_f) == 1 && $ar_f[0] == '')
            $ar_f = false;

        return $this->stringifyFromArray(($ar_f?$ar_f:[]), $delimiter);
    }

    public function stringifyFromArray(Array $fields, $delimiter = ',')
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

    public function updatefyFromArray(Array $fields, $delimiter = ',', $ignore_absents = false)
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

    public function updatefy(String $fields = null, $delimiter = ',', $ignore_absents = false)
    {
        $ar_f = explode(",", $fields);

        if(is_array($ar_f) && count($ar_f) == 1 && $ar_f[0] == '')
            $ar_f = false;

        return $this->updatefyFromArray(($ar_f?$ar_f:[]), $delimiter, $ignore_absents);
    }

    /**
     * Make empty or blank fields as null
     * 
     * @return object
     */
    public function TreatBlanksAsNull($v = true)
    {
        $this->blanks_as_null = $v;
        return $this;
    }

}