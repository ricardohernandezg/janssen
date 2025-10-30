<?php 

namespace Janssen\Traits;

use Janssen\Engine\Mapper;
use Janssen\Helpers\Exception;
use Janssen\Traits\InstanceGetter;
use Janssen\Traits\SQLWhere;
use Janssen\Traits\StaticCall;   

trait SQLStatement
{

    use InstanceGetter;
    use SQLWhere;
    use StaticCall;

    /**
     * Mode of query, defined by the last call to all, allById or one
     * 0 - all (default)
     * 1 - allById 
     * 2 - one
     * 3 - count
     * 
     * This variable is to be setted internally using the funcions
     * 
     * @var Integer
     */
    private static $query_mode = 0;

    private static $defaults = [
        'orderBy' => [],
        'distinct' => false,
        'zeroBasedMapping' => false,
        'mapping' => [],
        'limit' => -1,
        'offset' => -1,
        'queryMode' => 0
    ];

    protected static $zero_based_mapping = false;

    protected static $external_mapping;

    /** sql modifiers  */
    protected static $distinct = false;

    protected static $order_by = [];

    protected static $limit = -1;

    protected static $offset = -1;

    protected static $fields = [];    

    /**
     * Alias of makeBasicSelect()
     *
     * @return Object
     */
    public function prepareStatement(){
        return $this->makeBasicSelect();
    }

    /**
     * Makes the query string with the internal parted SQL object
     *
     * @return Object
     */
    public function makeBasicSelect()
    {
        
        $o = $this->prepareOrderBy();
        $lo = $this->prepareLimitOffset();
        $w = $this->prepareWhere();

        if(!self::$zero_based_mapping)
            $this->prepareMapping();

        self::$parted_sql = [
            'select' => ((self::$distinct)?'DISTINCT':'') . " * ", 
            'from' => ((self::$use_view)?$this->view:$this->table),  
            'where' => $w, 
            'orderby' => $o, 
            'limit' => $lo];
        
        return $this;
    }

    /**
     * Cleans the mapping 
     *
     * @return object
     */
    public static function clearMapping()
    {
        self::$external_mapping = self::$defaults['mapping'];
        return self::me();
    }

    /**
     * Prepares the where part of query
     *
     * @return String
     */
    protected function prepareWhere(){
        $w = '';
        // query mode 0 is all(), that can be all rows or all using where statement
        switch (self::$query_mode){
            case 0:
            case 3:
                $w = self::flatWhere();
                break;
            default:
                $w = $this->primaryKey . " = '" . self::$pk_value_for_query . "'";
        }
            
        return $w;
    }

    /**
     * Prepares the order by part of query
     *
     * @return String
     */
    protected function prepareOrderBy(){
        $o = '';
        if(count(self::$order_by) > 0)
        {
            foreach(self::$order_by as $k=>$v)
            {
                $o .= "$k $v, ";
            }
            $o = substr($o, 0, strlen($o)-2);
        }
        return $o;
    }
    
    /**
     * Returns the SQL parts that will be used to make the query string
     */
    public function getPartedSql() : Array
    {
        return self::$parted_sql;
    }

    /**
     * Sets the parts that Model will use to make the query string
     *
     * @param Array $parted_sql
     * @return Object
     */
    public function setPartedSql(Array $parted_sql)
    {
        //parted must be an Array and have all the members
        $parts = ['select','from','where','orderby','limit'];
        $f = true;
        foreach($parts as $v){
            $f = ($f && isset($parted_sql[$v]));
        }
        if($f){
            self::$parted_sql = $parted_sql;
            return $this;
        }else
            throw new Exception('Parted sql needs all its parameter to be built correctly.', 500);
    }


    /**
     * Sets the field array as select only to be flatten at query prepare time
     * 
     * @return Object
     */
    protected function prepareMapping()
    {
        // check if user restricted the query to only some fields
        $r = [];
        if(self::$fields){
            
            // check if there are mapping to that fields
            foreach(self::$fields as $v)
            {

                $mmfa = $this->manualMappingFromAlias($v);
                if($mmfa) 
                    $v = $mmfa[1];
                $em = $this->getExternalMapping($v);

                if($mmfa){
                    $r[] = "{$mmfa[0]} as '$em'";
                }elseif ($em)
                    $r[] = "$v as '$em'";
                else
                    $r[] = $v;
                        
            }
            self::$fields = $r;            
        }else{
            // user has not restricted the fields, we'll get all, but still 
            // we can have specific or rule mapping
            if(self::$external_mapping){
                foreach(self::$external_mapping as $k=>$v){
                    $r[] = "$k as '$v'";
                }
                self::$fields = $r;
            }
        }
        return $this;
    }

    private function getExternalMapping($field_name)
    {
        return (!empty(self::$external_mapping) && array_key_exists($field_name,self::$external_mapping)) ?
            self::$external_mapping[$field_name] : $field_name;
    }

    private function manualMappingFromAlias($field_name)
    {
        $field_name = trim($field_name);
        if(!strpos($field_name, ' ')) 
            return false;
        else{
            $found = false;
            do{
                $field_name = str_replace('  ', ' ', $field_name);
                $found = strpos($field_name, '  ');
            }while($found);
        }
            
        $er = '/(.+)\sas\s([a-zA-Z0-9_\-#@$]+)$/mi';
        $a = [];
        $i = preg_match($er,$field_name,$a);
        return ($i > 0) ? [$a[1],$a[2]] : false;
    }

    protected function clean()
    {
        // disable use associated view
        self::useView(self::$defaults['useView']);
        // clear distinct select
        self::distinct(self::$defaults['distinct']);
        // clear order by
        self::clearOrderBy();
        // clear specific field mapping
        self::clearMapping();
        // clear select fields created by mapping
        self::selectOnly([]);
        // clear limit and offset
        self::limit();
        self::offset();
        // make default query mode
        self::$query_mode = self::$defaults['queryMode'];
        // clear parted_sql
        self::$parted_sql = [];
        // clean where
        self::cleanWhere(); 
        // clean me() instance
        self::notMe();
        // set debugMode off
        self::$debug_and_wait = false;
        return $this;
    }

    // - - - - STATIC QUERY MODIFIERS  - - - -  //

    public static function selectOnly(Array $fields)
    {
        self::$fields = $fields;
        return self::me();
    }

    /**
     * Make the query as count(*)
     * 
     * @return int
     */
    public static function count()
    {
        self::$query_mode = 3;
        self::$fields = ['count(*) as count'];
        $r = self::me()->go();
        return (int) $r['count'];
    }

    public static function noMap(){
        self::$zero_based_mapping = true;
        self::$external_mapping = null;
        return self::me();
    }

    /**
     * Set a Mapper object for output fields
     *
     * @param Mapper $mapper
     * @return Object
     */
    public static function mapWith(Mapper $mapper)
    {
        self::$zero_based_mapping = false;
        self::$external_mapping = $mapper->getMap();    
        return self::me();
    }

    /**
     * Add specific field mapping to the next query
     *
     * @param String $field_name
     * @param String $new_name
     * @return Object
     */
    public static function addFieldMap($field_name, $new_name = '')
    {
        if(!empty($field_name))
            self::$external_mapping[$field_name] = empty($new_name)?$field_name:$new_name;

        return self::me();
    }

    /**
     * Add specific field mapping from array. 
     *
     * @param Array $map
     * @return Object
     */
    public static function addFieldMapFromArray(Array $map)
    {
        foreach($map as $k=>$v){
            self::addFieldMap($k, $v);
        }

        return self::me();
    }

    /**
     * Sets or disables the use of DISTINCT clause in query
     *
     * @param boolean $value
     * @return object
     */
    public static function distinct($value = true)
    {
        self::$distinct = $value;
        return self::me();
    }

    /**
     * Sets the order by fields in the query
     *
     * @param string $field
     * @param string $mode
     * @return object
     */
    public static function orderBy($field, $mode = '')
    {
        $mode = strtoupper(trim($mode));
        if(!in_array($mode, ['','ASC','DESC']) || empty($field))
            throw new Exception('Bad criteria for order query results');

        self::$order_by[$field] = $mode;
        return self::me();
    }

    /**
     * Cleans the order by clause
     *
     * @return object
     */
    public static function clearOrderBy()
    {
        self::$order_by = self::$defaults['orderBy'];
        return self::me();
    }

    /**
     * Sets the limit of rows in query
     *
     * @param integer $rows_count
     * @return object
     */
    public static function limit($rows_count = -1)
    {
        self::$limit = $rows_count;
        return self::me();
    }

    /**
     * Sets the offset in query
     * @todo Check the compatibility with engines. Actually offset works well 
     * with Postgres
     *
     * @param integer $rows_skip
     * @return object
     */
    public static function offset($rows_skip = -1)
    {
        self::$offset = $rows_skip;
        return self::me();
    }

}